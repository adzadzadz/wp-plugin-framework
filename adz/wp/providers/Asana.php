<?php 

namespace AdzHive\providers;

class Asana extends \AdzHive\Adz {

  /**
   * Service Provider
   * 
   * @var \Asana\Client 
   */
  public $_c;

  /**
   * User resource
   *
   * @var \Asana\Resources\Users
   */
  public $_u;

  public $accessToken = "1/1199983374664557:8e873e189eab88ca753a43aa5f3f409e";

  public function init() 
  {
    if ( !$this->accessToken ) return;
    $this->_c = $this->auth();
    $this->_c->options['page_size'] = 1000;
    $this->_u = $this->_c->users->getUser( "me" );
  }

  public function auth()
  {
    return \Asana\Client::accessToken( $this->accessToken );
  }

  public function getUser()
  {
    return $this->_u;
  }

  public function getWorkspaces()
  {
    return $this->_u->workspaces;
  }

  // Project GID: 1199983414725414
  public function getProjectsByWorkspace( )
  {
    return $this->_c->projects->getProjectsForWorkspace(1199983414254919, array('archived' => 'false'), array('opt_pretty' => 'true'));
  }

  public function getSections()
  {
    return $this->_c->sections->getSections(['project' => 1199983414725414]);
  }

  public function getTasks()
  { 
    return $this->_c->get("tasks", [], []);
  }

  public function getClient()
  {
    return $this->_c;
  }

  public function getTaskStories()
  {
    // $tasks = $this->_c->tasks->findAll(["section" => 1199983414725415], ["opt_style" => "pretty", "fields" => ['tags'], 'full_payload' => false]);
    $tasks = $this->_c->tasks->getTasks(["section" => "1199983414725415", "limit" => "20" ], ["opt_fields" => ["gid","name", "tags"]]);
    $dataTasks = [];
    foreach ( $tasks as $ix => $task ) {      
      $dataTasks[$ix]["task"] = [
        $task->gid,
        $task->name
      ];
      $stories =  $this->_c->stories->getStoriesForTask($task->gid, ["limit" => 100], ["opt_fields" => ["html_text","text","gid","resource_type","resource_subtype","created_at","created_by","target","is_pinned","is_edited","source"], "full_payload" => "false"]);
      foreach ($stories as $ixs => $story) {
        $dataTasks[$ix]["stories"][] = [
          'created_by' => $story->created_by,
          'resource_type' => $story->resource_type,
          'resource_subtype' => $story->resource_subtype, 
          'text' => $story->text,
          'html_text' => $story->html_text,
          'type' => $story->type,
        ];
      }
    }
    return json_encode($dataTasks, JSON_PRETTY_PRINT);
  }

}