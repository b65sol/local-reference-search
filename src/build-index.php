<?php
include 'vendor/autoload.php';
use TeamTNT\TNTSearch\TNTSearch;

class Index {
  protected $store;
  protected $tntconf;
  protected $tnt;
  protected $indexes = [];
  protected $indexbuffer = [];
  protected $int_to_ext_stmt;

  public function search($query, $index = 'textbody') {
    $result_obj = $this->tnt->searchBoolean($query);
    $extids = [];
    foreach($result_obj['ids'] as $ind) {
      $extids[] = $this->get_external_id($ind);
    }
    return $extids;
  }

  public function retrieve_document_external_id($external_id) {
    $stmt = $this->store->prepare("SELECT id, textbody, external_id FROM documents WHERE external_id = :extid");
    $stmt->execute([":extid" => $external_id]);
    return $stmt->fetchAll();
  }

  public function retrieve_document_internal_id($internal_id) {
    $stmt = $this->store->prepare("SELECT external_id, textbody, id FROM documents WHERE id = :id");
    $stmt->execute([":id" => $internal_id]);
    return $stmt->fetchAll();
  }

  /**
   * Initialize document store.
   */
  protected function init_db($sqlitefile) {
    $this->store = new \PDO('sqlite:'.$sqlitefile);
    $this->store->exec('CREATE TABLE  IF NOT EXISTS documents
      (id INTEGER PRIMARY KEY,
        external_id VARCHAR(255) UNIQUE NOT NULL,
        text_body TEXT
      )');
    $this->store->exec('CREATE TABLE IF NOT EXISTS document_meta (
      document_id INTEGER,
      meta_key VARCHAR(255),
      meta_value BLOB,
      meta_delta INTEGER,
      FOREIGN KEY(document_id) REFERENCES document_id(id)
    )');
    $this->store->exec('CREATE INDEX IF NOT EXISTS meta_key_index ON document_meta (meta_key)');
    $this->store->exec('CREATE INDEX IF NOT EXISTS docid_metakey ON document_meta(document_id, meta_key)');

    ob_start();
    if(!is_file($this->tntconf['storage'].'/textbody.index')) {
      $indexer = $this->tnt->createIndex('textbody.index');
      $indexer->query('SELECT id, text_body FROM documents;');
      //$indexer->setLanguage('german');
      $indexer->run();
    }
    ob_clean();
    $this->tnt->selectIndex('textbody.index');
    $this->indexes['textbody'] = $this->tnt->getIndex();
  }

  /**
   * Deletes a document based on it's external ID.
   * @param String $external_id
   * @return void
   */
  public function delete_document($external_id) {
    if($external_id === null || $external_id === '') {
      throw new \Exception("external_id parameter call to delete_document cannot be null or empty string.");
    }
    $docid = $this->resolve_external_id($external_id);
    $stmt = $this->store->prepare("DELETE FROM document_meta
        WHERE document_id IN
          (SELECT id FROM documents WHERE external_id = :extid)");
    $stmt->execute(array(':extid' => $external_id));
    $stmt = $this->store->prepare("DELETE FROM documents WHERE external_id = :extid");
    $stmt->execute(array(':extid' => $external_id));
    if($docid !== null) {
      $this->indexes['textbody']->delete($docid);
    }
  }

  public function delete_document_by_internal_id($internal_id) {
    if($internal_id === null || $internal_id === '') {
      throw new \Exception("internal_id parameter call to delete_document_by_internal_id ".
        "cannot be null or empty string.");
    }
    $stmt = $this->store->prepare("DELETE FROM document_meta
        WHERE document_id = :extid)");
    $stmt->execute(array(':extid' => $internal_id));
    $stmt = $this->store->prepare("DELETE FROM documents WHERE external_id = :extid");
    $stmt->execute(array(':extid' => $internal_id));
    $this->indexes['textbody']->delete($internal_id);
  }

  public function get_external_id($docid) {
    $this->int_to_ext_stmt->execute([':docid' => $docid]);
    $results = $this->int_to_ext_stmt->fetchAll();
    foreach($results as $result) {
      return $result['external_id'];
    }
    return null;
  }

  public function resolve_external_id($external_id) {
    $stmt = $this->store->prepare("SELECT id FROM documents WHERE external_id = :extid");
    $stmt->execute([':extid' => $external_id]);
    $results = $stmt->fetchAll();
    return empty($results[0]['id']) ? null : $results[0]['id'];
  }

  /**
   * Construct the core Index
   */
  public function __construct($sqlitefile, $indexdir = '') {
    if(empty($indexdir)) {
      $indexdir = $sqlitefile.'.index';
      if(is_file($indexdir) && !is_dir($indexdir)) {
        throw new \Exception('Default index store directory is not a directory, but exists.');
      }
      if(!is_dir($indexdir)) {
        mkdir($indexdir);
      }

      if(!is_dir($indexdir)) {
        throw new \Exception('Could not create index directory.');
      }
    }
    $this->tntconf = [
      'driver' => 'sqlite',
      'storage' => $indexdir,
      'database' => $sqlitefile,
    ];
    $this->tnt = new TNTSearch();
    $this->tnt->loadConfig($this->tntconf);
    $this->init_db($sqlitefile);
    $this->int_to_ext_stmt = $this->store->prepare("SELECT external_id FROM documents WHERE id=:docid");

  }

  public function beginTransaction() {
    $this->store->query("BEGIN TRANSACTION");
    $this->tnt->indexBeginTransaction();
  }

  public function endTransaction() {
    $this->store->query("END TRANSACTION");
    $this->tnt->indexEndTransaction();
  }

  /**
   * Record item
   */
  public function upsert_document($external_id, $content) {
    if($external_id === null || $external_id === '') {
      throw new \Exception('Cannot insert a document with no external id');
    }
    $stmt = $this->store->prepare("SELECT id FROM documents WHERE external_id = :extid");
    $stmt->execute([':extid' => $external_id]);
    $results = $stmt->fetchAll();
    $params = [":txt" => $content];
    if(!empty($results[0]['id'])) {
      $upsert = $this->store->prepare("UPDATE documents SET text_body = :txt WHERE id = :id");
      $params[":id"] = $results[0]['id'];
    } else {
      $upsert = $this->store->prepare("INSERT INTO documents (text_body, external_id) VALUES (:txt, :extid)");
      $params[":extid"] = $external_id;
    }
    $result = $upsert->execute($params);
    if($result == false) {
      throw new \Exception("Could not insert $external_id into data store.");
    }
    $docid = $this->resolve_external_id($external_id);
    //var_dump($this->indexes);
    $this->indexes['textbody']->update($docid, ['id' => $docid, 'article' => $content]);
  }

  /**
   * Store meta data. Set $value explicitly to null to delete.
   */
  public function set_meta($external_id, $key, $value, $delta = 0) {
    $docid = $this->resolve_external_id($external_id);
    if($docid === null) {
      throw new \Exception("$external_id was not found in document meta set.");
    }
    $lookup = $this->store->prepare("SELECT meta_key FROM document_meta
      WHERE meta_delta = :delta AND meta_key = :key AND document_id = :docid");
    $lookup->execute([':key' => $key, ':docid' => $docid, ':delta' => $delta]);
    $results = $lookup->execute();
    $params = [':docid' => $docid, ':key' => $key, ':delta' => $delta, ':value' => $value];
    if(!empty($results[0]['meta_key'])) {
      $upsert = $this->store->prepare("UPDATE document_meta SET meta_value=:value WHERE document_id = :docid AND meta_key = :key AND meta_delta = :delta");
    } else {
      $upsert = $this->store->prepare("INSERT INTO document_meta (document_id, meta_key, meta_delta, meta_value) VALUES (:docid, :key, :delta, :value)");
    }
    $result = $upsert->execute($params);
    if($result == false) {
      throw new \Exception("Could not insert document meta $key for document $docid");
    }
  }

  /**
   * Retrieve meta data.
   */
  public function get_meta($external_id, $key, $delta = null) {
    $docid = $this->resolve_external_id($external_id);
    if($docid === null) {
      throw new \Exception("$external_id was not found in document meta get.");
    }
    $params = [':key' => $key, ':docid' => $docid];
    if($delta === null) {
      $lookup = $this->store->prepare("SELECT meta_delta,meta_value FROM document_meta
        WHERE meta_key = :key AND document_id = :docid");
    } else {
      $params[':delta'] = $delta;
      $lookup = $this->store->prepare("SELECT meta_delta,meta_value FROM document_meta
        WHERE meta_key = :key AND document_id = :docid AND meta_delta = :delta");
    }
    $lookup->execute($params);
    $results = $lookup->fetchAll();
    $final = [];
    foreach($results as $result) {
      $final[$result['meta_delta']] = $result['meta_value'];
    }
    return $final;
  }
}
