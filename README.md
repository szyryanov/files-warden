Site Files Warden
=================

A simple but useful PHP tool to check changed files on a website.

Changes can be done by you as a website author... and by a malicious software like viruses. 
Using this tool, you can detect that your website is infected, even if it continues to look good.

How it works
------------

The tool saves a current state (directories and files list with date/time and size information) into a json file.
Then, after some time, you run the tool again, and it compares the saved state and present one.
The following changes can are detected:

- directory added
- directory deleted
- file added
- file edited (data/time or size changed)
- file deleted
- directory converted to file (with the same name)
- file converted to directory

You can exclude some directories and/or files from the scan if they change during normal website work (e.g. log files, data files etc.).

System requirements
-------------------

Most likely, any modern Linux hosting with PHP will be suitable. Tested on Apache 2.4 and PHP 5.5 environment.

How to install
--------------


1. Download the sources.
2. Edit index.php: add some authentication code at the beginning. Look at your CMS or website admin part sources for the guide. 
   For example, for some websites it can be something like following:

	    session_start();
	    if(!isset($_SESSION['user_name'])) {
	        header("Location: login.php");
	        exit;
	    }

    **You must prevent unauthorized persons from running the tool**. Otherwise, anyone can accept the changes, and then you will not know about them.

3. Upload the updated tool to your website `files-warden` directory. Or upload it to any other (sub)directory, but change the starting path in the 
index.php (`new FilesWarden("..")` parameter) and tool URLs in this manual accordingly.

4. Type `yourwebsite.com/files-warden/index.php?demo=1` in browser address bar to check that it works. You should see a demo page.
   Don't forget to change `yourwebsite.com` to your website address.
 
5. Type `yourwebsite.com/files-warden/index.php` (without `?demo=1`) in browser address bar to run the tool for the first time. 
   You will see all the website files and directories as added/created (because the tool have no idea that they existed before).
   
6. Click "Accept changes". Click "Check again". No changes? It works!

How to use
----------

Check changes from time to time. Accept your own changes (e.g. when you add or edit site content, install CMS plugins, and so on).
If you see unexpected changes - be afraid, and investigate the source of the changes.


Settings
--------

1. Initial path for scanning. The path is `new FilesWarden()` constructor parameter. By default it's set one level up from the tool script (i.e. `".."`).
   If you change the path after state saving, then you will see changes like old path was deleted, and new one was added.

2. Excluded files and directories. Call `$filesWarden->AddExclusion()` to exclude some path from scanning. The excluding path is relative to the initial path.
   
  	By default index.php calls `$filesWarden->AddExclusion('files-warden/data');` to exclude own data because they change every time you do scan.
   You can add additional exclusions. For the `AddExclusion()` parameter you can copy/paste path from change details popup title.


