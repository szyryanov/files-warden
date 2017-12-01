<?php 

      class FilesWarden {
      
        public $ExcludeItems = array();
      
        public $Path = null;

        private $STATE_FILENAME = "data/state.json";
        private $NEW_STATE_FILENAME = "data/new-state.json";
        
        private $prev = null;
        private $curr = null;
        private $diffItemsCount = 0;
        private $demoStep = 0; // 0 - not a demo (real work), 1 - show changes, 2 - no changes (after accept changes)
        private $excludeItemsDict = array();
        
        function __construct($path){
          $this->Path = $path;
          //
          if (isset($_GET['demo'])){
            $this->demoStep = intval($_GET['demo']);
            //
            $this->Path = '';
            $this->AddExclusion('demo/prev\dir1\subdir5');
            $this->AddExclusion('demo/curr\dir1\subdir5');
          }
        }
      
        public function DemoParms($step){
          if ($this->demoStep === 0) return ''; // not a demo mode
          $step = intval($step);
          return "?demo=$step";
        }
      
        public function DemoStepValue($step){
          if ($this->demoStep === 0) return ''; // not a demo mode
          $step = intval($step);
          return "$step";
        }
      
        public function AddExclusion($path){
          if ($this->Path !== '') $path = $this->Path . DIRECTORY_SEPARATOR . $path;
          $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
          $path = str_replace("/", DIRECTORY_SEPARATOR, $path);
          $this->excludeItemsDict[$path] = true; // the value does not matter: it can be true, false, 0, 1, '' etc.
        }
      
        public function CheckDiff(){
          //print_r($this->excludeItemsDict);
          if ($this->demoStep !== 0){
            return $this->checkDiffDemo();
          }
          //
          clearstatcache(true);
          //
          if (!is_dir($this->Path)){
            echo "directory not found: $this->Path <br />\r\n";
            return false;
          }
          //
          $prevStr = false;
          if (file_exists($this->STATE_FILENAME)) $prevStr = file_get_contents($this->STATE_FILENAME);
          //
          $this->prev = null;
          if ($prevStr === false){
            $this->prev = new State();
            $this->prev->Root->SubItems = array();
          }else{
            $this->prev = json_decode($prevStr);
          }
          //
          $this->curr = new State();
          $this->curr->DateTime = time();
          $this->curr->Root->SubItems = $this->scan($this->Path);
          //
          $currStr = json_encode($this->curr);
          file_put_contents($this->NEW_STATE_FILENAME, $currStr);
          //
          return true;
        }
        
        private function checkDiffDemo(){
          if (!is_dir("demo/prev")){
            echo "directory not found: demo/prev <br />\r\n";
            return false;
          }
          if (!is_dir("demo/curr")){
            echo "directory not found: demo/curr <br />\r\n";
            return false;
          }
          //
          $this->prev = new State();
          $this->prev->DateTime = 1511988430;
          $this->prev->Root->SubItems = $this->scan("demo/prev");
          //
          $currPath = "demo/curr";
          if ($this->demoStep === 2) $currPath = "demo/prev";
          $this->curr = new State();
          $this->curr->DateTime = time();
          $this->curr->Root->SubItems = $this->scan($currPath);
          //
          return true;
        }
        
        public function GetPrevDateTime(){
          return $this->prev->DateTime;
        }
        
        public function GetDiffItemsCount(){
          return $this->diffItemsCount;
        }
        
        public function PrintDiffHtml(){
          $diff = $this->calcDiff($this->prev->Root, $this->curr->Root);
          //
          $this->diffItemsCount = 0;
          echo "<div class=\"diff\">\r\n";
          echo "<ul>\r\n";
          if ($diff === null){
            $this->doPrintDiffHtml($diff);
          }else{
            foreach($diff->SubItems as $subItem) $this->doPrintDiffHtml($subItem);
          }
          echo "</ul>\r\n";
          echo "</div>\r\n";
        }
        
        public function SaveCurrentAsNorm(){
          if ($this->demoStep !== 0) return true;
          //
          return rename($this->NEW_STATE_FILENAME, $this->STATE_FILENAME);
        }
        
        // ---------------------------------------
        
        function scan($path){
          $subItems = array();
          //
          $names = scandir($path);
          if ($names !== false){
            foreach ($names as $name){
              if (($name !== '.') && ($name !== '..')){
                $fullName = $path . DIRECTORY_SEPARATOR . $name;
                //echo $fullName . "<br />\r\n";
                if (!isset($this->excludeItemsDict[$fullName])){                
                  $item = null;
                  if (is_dir($fullName)){
                    $item = new DirDirItem($name);
                    $item->SubItems = $this->scan($fullName);
                  }else{
                    $item = new DirFileItem($name);
                    $stat = stat($fullName);
                    $item->Size = $stat['size'];
                    $item->DateTime = $stat['mtime'];
                    //$item->Size = filesize($fullName);
                    //$item->DateTime = filemtime($fullName);
                  }
                  $subItems[] = $item;
                }
              }
            }
          }
          //
          return $subItems;
        }
        
        function printItem($item, $indent){
          if ($item->IsDir){
            echo $indent . "<strong>" . $item->Name . "</strong>" . "\r\n";
            foreach ($item->SubItems as $subItem) $this->printItem($subItem, $indent . "  ");
          }else{
            echo $indent . $item->Name . " " . $item->Size . " " . date(DATE_ATOM, $item->DateTime) . "\r\n";
          }
        }
        
        function calcDiff($prev, $curr){
          $diffSubItems = array();
          //
          $deletedSubItems = array();
          if (($prev !== null) && ($prev->IsDir)){
            foreach($prev->SubItems as $psi) $deletedSubItems[$psi->Name] = $psi;
          }
          //
          if (($curr !== null) && ($curr->IsDir)){
            foreach($curr->SubItems as $currSubItem){
              $prevSubItem = $this->findSubItem($prev, $currSubItem, $deletedSubItems);
              $diffSubItem = $this->calcDiff($prevSubItem, $currSubItem);
              if ($diffSubItem !== null){
                $diffSubItems[] = $diffSubItem;
              }
              //
              if ($prevSubItem !== null){
                $deletedSubItems[$prevSubItem->Name] = null;
              }
            }
          }
          //
          foreach($deletedSubItems as $deletedItemName => $deletedSubItem){
            if ($deletedSubItem !== null){
              $diffSubItems[] = $this->calcDiff($deletedSubItem, null);
            }
          }
          //
          if ($this->areDifferent($prev, $curr) || (count($diffSubItems) > 0)){
            return new DiffItem($prev, $curr, $diffSubItems);
          }
          return null;
        }
        
        function findSubItem($item, $subItemPattern, $itemSubItemsDict){
          if ($item === null) return null;
          //
          if (!$item->IsDir) return null;
          //
          if (isset($itemSubItemsDict[$subItemPattern->Name])){
            return $itemSubItemsDict[$subItemPattern->Name];
          }
          //
          /*
          foreach($item->SubItems as $subItem){
            //if (($subItem->IsDir === $subItemPattern->IsDir) && ($subItem->Name === $subItemPattern->Name)){
            if ($subItem->Name === $subItemPattern->Name){
              return $subItem;
            }
          }
          */
          return null;
        }
        
        function areDifferent($prev, $curr){
          if ($prev === null) return true;
          if ($curr === null) return true;
          if ($prev->IsDir !== $curr->IsDir) return true;
          //
          if (!$prev->IsDir){
            if ($prev->Size !== $curr->Size) return true;
            if ($prev->DateTime !== $curr->DateTime) return true;
          }
          //
          return false;
        }
        
        function printDiff($diff, $indent = ""){
          if ($diff === null){
            echo $indent . "No changes.\r\n";
            return;
          }
          //
          $change = "?";
          $name = "?";
          $isDir = false;
          if ($diff->Prev === null){
            $change = "inserted";
            $name = $diff->Curr->Name;
            $isDir = $diff->Curr->IsDir;
          }else if ($diff->Curr === null){
            $change = "deleted";
            $name = $diff->Prev->Name;
            $isDir = $diff->Prev->IsDir;
          }else{
            $change = "edited";
            $name = $diff->Curr->Name;
            $isDir = $diff->Curr->IsDir;
          }
          //
          if ($isDir) $name = '<strong>' . $name . '</strong>';
          echo $indent . $name . ': ' . $change . "\r\n";
          //
          foreach($diff->SubItems as $subItem) $this->printDiff($subItem, $indent . "  ");
        }

        function doPrintDiffHtml($diff, $indent = "  "){
          if ($diff === null){
            echo $indent . "<li>No changes.</li>\r\n";
            return;
          }
          //
          $this->diffItemsCount++;
          $change = "?";
          $name = "?";
          $fa = "";
          $isDir = false;
          if ($diff->Prev === null){
            $change = "inserted";
            $name = $diff->Curr->Name;
            $isDir = $diff->Curr->IsDir;
            $fa = "<i class=\"change fa fa-plus\"></i>";
          }else if ($diff->Curr === null){
            $change = "deleted";
            $name = $diff->Prev->Name;
            $isDir = $diff->Prev->IsDir;
            $fa = "<i class=\"change fa fa-minus\"></i>";
          }else{
            if ($diff->Prev->IsDir === $diff->Curr->IsDir){
              if ($diff->Curr->IsDir){
                $change = "edited";
                $name = $diff->Curr->Name;
                $isDir = true;
                $fa = "";
              }else{
                $change = "edited";
                $name = $diff->Curr->Name;
                $isDir = false;
                $fa = "<i class=\"change fa fa-asterisk\"></i>";
              }
            }else{
              $change = "edited";
              $name = $diff->Curr->Name;
              $isDir = $diff->Curr->IsDir;
              $fa = "<i class=\"change fa fa-asterisk\"></i>";
            }
          }
          //
          if ($isDir) $name = '<strong>' . $name . '</strong>';
          //
          $dataPrev = "data-prev=\"" . $this->calcData($diff->Prev) . "\"";
          $dataCurr = "data-curr=\"" . $this->calcData($diff->Curr) . "\"";
          $data = "$dataPrev $dataCurr";
          //
          if (count($diff->SubItems) > 0){
            echo $indent . "<li class=\"diffitem $change\" " . $data . ">\r\n";
            echo "$indent  <p class=\"name container\"><i class=\"roll fa fa-minus-square-o\"></i>$fa$name</p>\r\n";
            echo $indent . "  <ul>\r\n";
            foreach($diff->SubItems as $subItem) $this->doPrintDiffHtml($subItem, $indent . "    ");
            echo $indent . "  </ul>\r\n";
            echo $indent . "</li>\r\n";
          }else{
            echo "$indent<li class=\"diffitem $change leaf\" " . $data . "><p class=\"name\">$fa$name</p></li>\r\n";
          }
        }
        
        function calcData($item){
          if ($item === null) return '';
          //
          if ($item->IsDir){
            return 'dir';
          }else{
            return 'file|' . $item->Size . '|' . date('Y-m-d H:i:s P', $item->DateTime);
          }
        }

      }
      
      class DirItem {
        public $Name;
        public $IsDir;
        //
        function __construct($name, $isDir){
          $this->Name = $name;
          $this->IsDir = $isDir;
        }
      }
      
      class DirDirItem extends DirItem {
        public $SubItems;
        //
        function __construct($name){
          parent::__construct($name, true);
        }
      }
    
      class DirFileItem extends DirItem {
        public $Size;
        public $DateTime;
        //
        function __construct($name){
          parent::__construct($name, false);
        }
      }
      
      class DiffItem {
        public $Prev;
        public $Curr;
        public $SubItems;
        //
        function __construct($prev, $curr, $subItems){
          $this->Prev = $prev;
          $this->Curr = $curr;
          $this->SubItems = $subItems;
        }
      }
      
      class State {
        public $DateTime;
        public $Root; // DirDirItem
        //
        function __construct(){
          $this->DateTime = null;
          $this->Root = new DirDirItem('root');
        }
      }
      
?>
