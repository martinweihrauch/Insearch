<?php
/*
########################################################################################
## INSEARCH V1.0beta - A PHP search engine                                           ##
########################################################################################
##  Copyright (C) 2012 Smart In Media GmbH & Co. KG                                   ##
## INSEARCH                                                                          ##
##  http://www.smartinmedia.com                                                       ##
##                                                                                    ##
########################################################################################

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or any later version.

1. YOU MUST NOT CHANGE THE LICENSE FOR THE SOFTWARE OR ANY PARTS HEREOF! IT MUST REMAIN AGPL.
2. YOU MUST NOT REMOVE THIS COPYRIGHT NOTES FROM ANY PARTS OF THIS SOFTWARE!
3. NOTE THAT THIS SOFTWARE CONTAINS THIRD-PARTY-SOLUTIONS THAT MAY EVENTUALLY NOT FALL UNDER (A)GPL!
4. PLEASE READ THE LICENSE OF THE CUNITY SOFTWARE CAREFULLY!

	You should have received a copy of the GNU Affero General Public License
    along with this program (under the folder LICENSE).
	If not, see <http://www.gnu.org/licenses/>.

   If your software can interact with users remotely through a computer network,
   you have to make sure that it provides a way for users to get its source.
   For example, if your program is a web application, its interface could display
   a "Source" link that leads users to an archive of the code. There are many ways
   you could offer source, and different solutions will be better for different programs;
   see section 13 of the GNU Affero General Public License for the specific requirements.

   #####################################################################################
   */
//connect to your database
require_once('insearch.class.php');
require_once('db_config.php');
$searchstring = trim($_GET['term']);//retrieve the search term that autocomplete sends
if ($searchstring=='' || $searchstring==null) die ('The search string is not valid!');


//You have to adapt the following code so that it uses the correct database connection!

if (!$ins = new insearch('standard',  $db_host , $db_user, $db_pass, $db_name)) die ('<br />Cannot start a new insearch object!');


$searchresults = array();//This array will store the search results
$ins->searchWriting($searchstring, $searchresults, '');
echo json_encode($searchresults);//format the array into json data



?>