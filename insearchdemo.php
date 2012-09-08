<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- 
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
 -->

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">

<head>
    <title>Insearch Demo</title>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="keywords" content="" />
    <link type="text/css" href="styles/jquery-ui-1.8.23.custom.css" rel="stylesheet" />
		<script type="text/javascript" src="javascript/jquery-1.8.0.min.js"></script>
		<script type="text/javascript" src="javascript/jquery-ui-1.8.23.custom.min.js"></script>
        <script type="text/javascript" src="javascript/insearch.js"></script>

</head>

<div style="width:650px;margin-left:auto;margin-right:auto;height:auto;">

<?php

error_reporting(-1);
require_once('db_config.php');
require_once('insearch.class.php');
if (isset($_REQUEST['searchwriting'])){
    if ($_REQUEST['insearchwriting']=="") echo "<br /><h2>Please fill out the search field! </h2>";
    else {
    
            if (!$ins = new insearch('standard', $db_host , $db_user, $db_pass, $db_name)) echo '<br />ERROR: NO DATABASE CONNECTION! Please enter the parameters correctly!';
            $fullresults = array();
            $searchstring = $_REQUEST['insearchwriting'];
            $ins->fullSearch($searchstring, $fullresults, 10, $language='en');
            echo '<div style="padding:20px;background-color:#004927;color:white;font-weight:bold;"><h2>Search results:</h2><br />';
            echo"Your entered string: ".$searchstring;
            echo "<br>The returned array with the search results represented with article IDs in descending importance (first most important search result and so forth):<br />";
            print_r($fullresults);
            echo"</div>";
    }
}

if (isset($_REQUEST['submit'])){
    
echo '<h1>I am in</h1>';
echo 'Insearch demo <br />';




//The following line is to get a new object. The constructor gets
// insearch (string modulename, string databasehost, string databaseuser, string databasepassword, string databasename);
if (!$ins = new insearch('standard', $db_host , $db_user, $db_pass, $db_name)) echo '<br />ERROR: NO DATABASE CONNECTION!';

//First, you can decide whether occurrences are important for the search ranking or not
$ins->setUsecount(true); //If true, it means that the number of occurrences of a word is a factor for its ranking in the search results. If set to "false", then the number
                        //of occurrences will be ignored (to prevent that users may add 100times a certain word to be ranked at the top of the search results)



//Now we want to add an article First, we have to define the article structure and the weight of the parts of the article
//THis is done in a 2-dimensional associative array (e. g. $articlestructure). The 'parts' needs the name of the parts and the 'weight' the respective weight of the parts.
//You define the weight. E. g. if you think that the title is very important, the weight could be 3, and the weight of the text is 1

//The weight is a float-value

$articlestructure = array('parts'=>array('title', 'content'), 'weight'=>array(3, 1));
 
 //Now we construct the article array. This is a simple array, where the first value holds the first article part of the 
 //article structure and the second value the second article part (and so forth). 
 //In our example, we only have 2 parts (title, content)

$articletexts = array($_REQUEST['atitle'], $_REQUEST['atext']);

//Lets add the article words to the insesarch database
//The function is addArticle(int articlenumber,array articlestructure,array articletexts, string language,float rating);

//Only supported languages are English ('en') and German ('de')
//language and rating are optional
//THE ARTICLENUMBER MUST BE UNIQUE!
//The errormessage in case of error of the last error is stored in the property errormessage

if (!$ins->addArticle(intval($_REQUEST['anr']), $articlestructure, $articletexts, 'en', 3.0)){
    echo '<br /><span style="color:red;font-weight:bold;">ERROR: '.$ins->errormessage.'</span>';
} else echo '<br /><br/><span style="color:green;"><strong>Your article was successfully added! You should now be able to search it!</strong></span>';

// TO DELETE AN ARTICLE, YOU USE:
//$ins->deleteArticle($articleid);

// TO UPDATE AN ARTICLE, YOU USE:
//$ins->updateArticle($articleid);
//NOTE THAT the update function first removes and then adds the article


/*$fullresults = array();
$searchstring = 'us-wirtschaftsembar';
$ins->fullSearch($searchstring, $fullresults, 10, $language='de');
echo"<br /><br />SEARCH STRING: ".$searchstring;
echo "<br>SEARCH RESULTS: ";
print_r($fullresults);

$stoptime = microtime(true);

echo '<br /><br /><br />ERRORMESSAGE:'.$ins->errormessage;
echo '<br />Start: '.$starttime;
echo '<br /><br />Zeit verbraucht: '.(string)($stoptime-$starttime);
*/
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">

<head>
    <title>Insearch Demo</title>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="keywords" content="" />
    <link type="text/css" href="styles/jquery-ui-1.8.23.custom.css" rel="stylesheet" />
		<script type="text/javascript" src="javascript/jquery-1.8.0.min.js"></script>
		<script type="text/javascript" src="javascript/jquery-ui-1.8.23.custom.min.js"></script>
        <script type="text/javascript" src="javascript/insearch.js"></script>

</head>

<body>
  <h2>Insearch Demo</h2>
  <p>Copyright &copy; by Smart In Media 2012 under (A)GPL</p>
  <br />Welcome to the insearch demo. Insearch is a PHP index search class with 3-MySQL tables that will add a search functionality to your website.
  In order to do that, every article that is added to your website and which should be searchable has to be transmitted to the insearch class by easy 
  functions. An "article" could be a forum post or a new blog entry, etc. Note that insearch will NOT save your article. You have to do that yourself.
  Insearch only takes the entire article and makes an index from the words within the article.  
  <br />
  <br />Also, you tell insearch, which structure your article has. E. g. it may have a title, a content and an author. You add weights to these article 
  parts. E. g. you could give the title the weight 3 and the content the weight 1. Also, you can rank the entire article. E. g., if your site has 
  a review functionality, where users can rate articles (e. g. 1 to 5 stars), then you also tell that insearch. 
  <br />Insearch will - after a search request - order the articles by the ranking, the weight and the occurrence of the words in an article (although this
  is weighted only by a tenth of the other two). You can also switch off the occurrence-parameter as you may want to prevent users from writing 100x a certain
  word in an article in order to get a higher ranking in search results.
  <strong>When you start integrating this class, look into this demo-file (and also the javascript insearch.js for search while writing) how it is done!</strong>
  <br /> 
  <br />Insearch also offers a "search while writing" functionality (thanks to jqueryUI and an ajaxcontroller), which shows up to 7 results while typing.
  <br /><br /><h3>Insearch has these nice features:</h3>
  <ul>
    <li>It automatically creates the 3 insearch tables in your database</li>
    <li>It removes possible MySQL-injections with mysql_real_escape_string</li>
    <li>It removes any HTML tags to prevent scripts</li>
    <li>It removes "stopwords", e. g. "the", "usually", etc</li>
    <li>It removes "badwords", e. g. #*~!# :)</li>
    <li>It supports more than only English: currently, also German in implemented</li>
    <li>It stores the word once in the original form and then in again in a form without special characters. E. g. "&eacute;" is also stored as "e" and &ouml; is also stored as "oe".</li>
    <li>You can manage multiple modules with one insearch class (and its 3 database tables): e. g. a forum, a blog and your pages. Thus, users only search in one module at a time.</li>
    <li>It can be used freely at no cost under Affero GNU PUBLIC LICENSE. If the software, in which you want to integrate, is not GPL, you have to inquire about another (paid) license.</li>
  </ul>
  
  <br /><br />
  <div style="background-color:#BABAEA">
  <h2>You can test the "add an article" here </h2>
  <br />
  <form method="post" action="insearchdemo.php">
   
   <strong>&gt;&gt;&gt; Important! First, you have to set the correct database connection in the "db_config.php" in the same directory!!&lt;&lt;&lt;</strong>
   <br /><br /><br />
   
   <table>

     <tr>
        <th>Add article</th>
        <tr>
        <td>ArticleID (must be unique number): </td><td><input type="text" name="anr" size="10" value="1"/></td>
        </tr>
        
        <tr>
        <td>Title: </td><td><input type="text" name="atitle" size="30" value=""/></td>
        </tr>
        <tr>
        <td>Text: </td><td><textarea name="atext" cols="30" rows="10">Add your text here...</textarea></td>
        </tr>
     </tr>
    </table>
       <input type="submit" name="submit" value="Add article to database"/>
  </form>
  
  </div>
 <br /><br /> 
  <div style="background-color:#E3CFCF;border:3px solid red;height:500px;" >
    <h2>You can test the searching (of previously added articles) here </h2>
  <br />
    <form method="post" action="insearchdemo.php">
        <label for="insearchwriting">SEARCH WHILE WRITING: </label>
    	<input id="insearchwriting" name="insearchwriting" size="50" />
    	<input type="submit" name="searchwriting" value="Lets go!"/>
    </form>  
  </div>

<br /><br /><div><h2>How to implement the class and use it:</h2></div>
<ul>
<li>1. Put your database settings into the db_config.php</li>
<li>2. You dont have to create insearch tables. The class will add 3 tables automatically during the first request.</li>
<li>3. Then create the object, e. g. if (!$ins = new insearch('standard', $db_host , $db_user, $db_pass, $db_name)) echo '<br />ERROR: NO DATABASE CONNECTION!';
</li>
<li>4. If you want that the search results are also ranked by the number of occurrences of one word in the article, you dont have to add anything. If you dont want that, use $ins->setUsecount(true); </li>
<li>5. To add an article, you have to do these steps:</li>
<li>a) Create a 2-dimensional associative array that tells the class the structure of the article and the weight of each part:<br />$articlestructure = array('parts'=>array('title', 'content'), 'weight'=>array(3, 1));
b) Put the texts in a simple array that has the structure of your articlestructure<br />
c) Add the article and give it a unique articlenumber also the language ('de' for German, 'en' for English) and the ranking of the article (float-value). You dont need language (en is default) or ranking.
<br />
if (!$ins->addArticle($articlenumber, $articlestructure, $articletexts, 'en', 3.0))....
</li>

<li>6. To delete or update an article, you use the functions deleteArticle or updateArticle.</li>
<li>7. If you want to perform a search, send the searchstring with $ins->fullSearch($searchstring, $fullresults, 10, $language='de');<br />
An array that you give the function will be filled with your article IDs in the order of importance. First entry most important, last entry least important. 
The search while writing starts after the entry of 2 letters by the way so that the server is not bugged too much with unnecessary work.</li>
<li>8. If you want to use "search while writing" you need javascript and the ajaxcontroller, both included in this package. See this insearchdemo.php for how to implement it.</li>
<li>9. We would be very grateful for donations over Paypal: order(at)smartinmedia(dot)com. Thanks!!</li>
</ul>

  
  <br /><br />
  Copyright &copy; by Smart In Media GmbH &amp; Co. KG 2012. Distributed under Aferro GNU Public License.    
</div>
</body>
</html>
