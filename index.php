<?php

  //error_reporting(E_ALL | E_STRICT);
  error_reporting(0);
  require_once('diff.classes.php');
  $filesWarden = new FilesWarden("..");
  //$filesWarden = new FilesWarden("You are not allowed to check changes on this site in real mode. Use <a href=\"?demo=1\">demo mode</a> instead.");

  $filesWarden->AddExclusion('files-warden/data');
  
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
  <title>Site Files Warden</title>
  <meta name="keywords" content="">
  <meta name="description" content="">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <style>
    
    /* ---------------------- */
    /*        reset           */
    /* ---------------------- */

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    /* ---------------------- */
    /*         page           */
    /* ---------------------- */
  
    .page {
      padding: 20px;
      font-family: Geneva, Arial, Helvetica, sans-serif;
    }
  
    .demo-explanation {
      background: #eee;
      padding: 20px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
    }
  
    h1,h2 {
      margin-bottom: 10px;
    }
    
    form {
      margin-top: 20px;
      margin-bottom: 20px;
    }
    
    input {
      padding: 5px;
    }
    
    .additional-notes {
      color: #777;
    }
  
    /* ---------------------- */
    /*         diff           */
    /* ---------------------- */
  
    .diff p {
      margin: 0;
      padding: 2px 50px 2px 5px;
    }
    .diff > ul {
      display: inline-block;
      border: 1px solid #ccc;
      padding: 10px 20px 10px 10px;
    }
    .diff ul {
      list-style: none;
      padding-left: 20px;
    }

    .fa.roll {
      font-size: 15px;
      margin-right: 7px;
      cursor: pointer;
    }       
    .fa.change {
      background-color: #fff;
      padding: 4px 3px 2px 3px;
      border-radius: 9px;
      font-size: 15px;
      margin-right: 5px;    
      cursor: pointer;
    }   
    .fa-minus {
      color: blue;
    }
    .fa-plus {
      color: red;
    }
    .fa-asterisk {
      color: #777;
    }
    
    /*
    
    .diff .fa.roll {
      font-size: 15px;
      margin-right: 7px;
      cursor: pointer;
    }   
    
    .diff .fa.change {
      background-color: #fff;
      padding: 4px 3px 2px 3px;
      border-radius: 9px;
      font-size: 15px;
      margin-right: 5px;    
      cursor: pointer;
    }   
    .diff .fa-minus {
      color: blue;
    }
    .diff .fa-plus {
      color: red;
    }
    .diff .fa-asterisk {
      color: #777;
    }
    
    */
    
    .edited > .name{
      background-color: #00ff00;
    }    
    .inserted .name {
      background-color: #ff88c8;
    }
    .deleted .name {
      background-color: #0088ff;
    }
    
    /* ------------------------------ */
    /*            details             */
    /* ------------------------------ */
    
     /* The Modal (background) */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    /* Modal Content/Box */
    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        /*padding: 20px;*/
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
    }

    /* The Close Button */
    .close {
        color: #888;
        float: right;
        font-size: 28px;
        font-weight: bold;
        margin-right: 5px;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
    } 
    
    .modal h4 {
      background: #ccc;
      padding: 10px;
    }
    .info {
      margin: 10px;
    }
    .info h5 {
      display: inline-block;
    }
    .info p {
      display: inline-block;
    }
    
  </style>
</head>
<body>
  
  <div class="page">
  
    <?php if ($filesWarden->DemoStepValue(2) !== ''){ ?>
    <div class="demo-explanation">
      Demo mode. It just emulates real work. <br />
      BTW you can click directory expand/collapse icons ( <i class="roll fa fa-minus-square-o"></i> <i class="roll fa fa-plus-square-o"></i> ), 
      and change circles ( <i class="change fa fa-asterisk"></i> <i class="change fa fa-plus"></i> <i class="change fa fa-minus"></i>)
      in the changes tree.
    </div>
    <?php } ?>
  
  
    <h1>Site Files Warden</h1>
    
    <?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST'){
      $message = "State saved";
      if (!$filesWarden->SaveCurrentAsNorm()){
        $message = "File rename error.";
      }
    
      ?>
      
      <p><?php echo $message; ?></p>   
      <form method="get" action="">
        <?php if ($filesWarden->DemoStepValue(2) !== ''){ ?>
        <input type="hidden" name="demo" value="<?php echo $filesWarden->DemoStepValue(2); ?>" />
        <?php } ?>
        <input type="submit" value="Check again" />
      </form>
    
    
    <?php }else{ 

      if ($filesWarden->CheckDiff()){
        $prevDateTime = $filesWarden->GetPrevDateTime();
        if ($prevDateTime === null){
          echo "<h2>First time check: all files looks like added.</h2>";
        }else{
          $prevDateTimeText = date('Y-m-d H:i:s P', $prevDateTime);
          echo "<h2>Changes found <small>(since $prevDateTimeText):</small></h2>";
        }
        //
        $filesWarden->PrintDiffHtml();
      }
      //
      $acceptChangesDisabled = '';
      if ($filesWarden->GetDiffItemsCount() === 0) $acceptChangesDisabled = "disabled=\"disabled\"";
      
    ?>
    
    <form method="post" action="<?php echo $filesWarden->DemoParms(2); ?>">
      <input type="submit" value="Accept changes" <?php echo $acceptChangesDisabled; ?> />
      <span>
        Current state will be saved as a norm. Next time a new state will be compared with this one.
      </span>
    </form>
    <p class="additional-notes">
      This tool does not change anything except 2 own state files inside the "data" directory. "Accept changes" only means update the state files.
    </p>
  </div>
  
  <div id="details" class="modal">
    <div class="modal-content">
      <span id="details-close" class="close">&times;</span>
      <h4 id="details-title" >dir/dir/dir/dir/dir/dir/dir/dir/dir/dir/dir/dir/dir/dir/</h4>
      <div class="info">
        <h5>Was:</h5>
        <p id="details-prev" >Nothing</p>
      </div>
      <div class="info">
        <h5>Now:</h5>
        <p id="details-curr">File, 100500 bytes, 2005-08-15 15:52:01</p>
      </div>
      <div class="info">
        <h5>Change:</h5>
        <p id="details-change">File inserted</p>
      </div>
    </div>
  </div>
  
  <script>
    $(document).ready(function () {
    
      $(".diff i.roll").click(function(){
        $fa = $(this);
        $name = $fa.parent();
        if ($fa.hasClass("fa-minus-square-o")){
          $fa.removeClass("fa-minus-square-o");
          $fa.addClass("fa-plus-square-o");
          $name.next().hide();
        }else{
          $fa.removeClass("fa-plus-square-o");
          $fa.addClass("fa-minus-square-o");
          $name.next().show();
        }
      });
    
      $(".diff i.change").click(function(){
        $fa = $(this);
        var path = '';
        $fa.parents(".diffitem").each(function(index, element){
          if (path !== '') path = '\\' + path;
          $li = $(this);
          $p = $li.children("p.name");
          path = $p.text() + path;
        });
        //
        $parent = $fa.parent().parent();
        dataPrev = $parent.attr('data-prev');
        dataCurr = $parent.attr('data-curr');
        //
        $("#details-title").text(path);
        $("#details-prev").text(calcStateText(dataPrev));
        $("#details-curr").text(calcStateText(dataCurr));
        $("#details-change").text(calcChangeText(dataPrev, dataCurr));
        //
        $("#details").show();
      });
      
      $(".modal").click(function(event){
        if (event.target === $(".modal")[0]){
          $("#details").hide();
        }
      });
      $("#details-close").click(function(){
        $("#details").hide();
      });
      
      function calcStateText(state){
        if (state === '') return 'Nothing';
        if (state === 'dir') return 'Directory';
        var parts = state.split('|');
        return 'File, ' + parts[1] + ' bytes, ' + parts[2];
      }
      
      function calcChangeText(prevState, currState){
        switch(prevState){
          case '': switch(currState){
            case '': return '???';
            case 'dir': return 'Directory created.';
            default: return 'File added.';
          }
          case 'dir': switch(currState){
            case '': return 'Directory deleted.';
            case 'dir': return '';
            default: return 'Directory converted to file (directory deleted, file added).';
          }
          default: switch(currState){
            case '': return 'File deleted.';
            case 'dir': return 'File converted to directory (file deleted, directory created).';
            default: return 'File edited.';
          }
        }
      }
          
    });
  </script>
  
  <?php } ?>
  
</body>
</html>

