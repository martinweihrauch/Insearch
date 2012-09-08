<?php
//TODO: Funktion zum Kürzen vom Array korrigieren!

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
Class insearch{
    private $articleparts = array();//These are the parts of one article, e. g. title, content, info, etc
    private $articleweight = array();//This contains the matching "weights" of the articleparts. E. g. the headline can count as double weighted compared to the content text during searches
    private $partscount = 0; //The number of array-items of $articleparts
    private $db; //The database object
    public  $errorstatus = false; //Errorstatus true/false
    public  $errormessage = ''; //This stores the last errormessage
    private $language = 'en'; //This is the language for stop-words, etc. 2 options: 'de' or 'en'
    private $module = ''; //This is the module that the articles are from (e. g. "forum", "newspaper"). Standardvalue is "standard"
    private $stopwords = array();
    private $badwords = array();
    private $use_count = true; ////This means that the number of occurrences of a word is a factor for its ranking in the search results. If set to "false", then the number
                        //of occurrences will be ignored (to prevent that users may add 100times a certain word to be ranked at the top of the search results)
    
    
    /*
     * Constructor: parameters: 
     * 1. an array with the group names for searches (e. g. "title", "documenttext", "url")
     * $articleparts must be in the form "title", "content", etc
     * 2. a mysqli-object for the database connection
     */
    function __construct($module='standard', $host, $user, $password, $database){
        require_once('stopwords.php');
        $this->setModule($module);
        $this->stopwords = $stopwords;
        $this->badwords = $badwords;
        $this->db_connect($host, $user, $password, $database);
    }
    
    
    function __destruct(){
        $this->db->close();
    }
    
    private function getCurrentDB() {
    $result = $this->db->query("SELECT DATABASE()") or die($this->db->errno);
    $row = $result->fetch_array();
    return $row;
    
    }
    
    public function setModule($module='standard'){
        if (count($module)>20) die ('<br>The method setModule only takes variables with up to 20 characters!');
        
        $this->module = $module;
        return true;
    }
    
    public function setUsecount($use_count=true){
        if ($use_count) $this->use_count = true;
        else $this->use_count = false;
    }
    
   
    private function db_connect($host, $user, $password, $database){
            $this->db = new mysqli($host, $user, $password, $database);
            if ($this->db->connect_error) {
                return false;
                $this->errormessage='Connect Error '.$this->db->connect_errno.' '.$this->db->connect_error;
            }
            $curdb = $this->getCurrentDB();
            $result = $this->db->query("SELECT COUNT(*) FROM information_schema.tables 
            WHERE (table_schema = '".$curdb."' AND table_name = 'in_words') 
            OR  (table_schema = '".$curdb."' AND table_name = 'in_articles') 
            OR  (table_schema = '".$curdb."' AND table_name = 'in_occurences')");
            $row = $result->fetch_array();
            
            if ($row[0]==1 || $row[0]==2 || $row[0]>3){ //If 1 or 2 tables were created, but the other are missing, that is an error!
                $this->errormessage='One or two of the required tables for insearch already exist, but the other is/are missing. Please fix this problem!';
                return false;
                
            }
            if ($row[0]==0){ //If the tables are not in place, they are created now
                require_once('insearch_db.sql.php');
                foreach ($sql_db as $value){
                      if (!$this->db->query($value)) {$this->errormessage ="<br />The insearch tables could not be added to your database. Please check!"; return false;}
                }
                return true;
                                    
            }
            //Else , the 3 tables seem to exist  --> do nothing
       return true;
    }

    public function addArticle($articlenumber, array $articlestructure, array $articletexts, $language='en', $rating=null){
        /*$articlenumber = the id that you have for your article in your database
         * $articletexts is an array with the texts in the order of the array $articlestructure
         *    $articlestructure is an associative array in the following structure:
            articlestructure['parts']=array('title', 'content', 'author', etc....) dexcribing the parts that will be delivered in the $articletexts
            articlestructure['weight']=array(3, 2, 1) is the weight of the parts (meaning the importance for the search --> here, title is more important
            than content and this is more important than the author)
            The weight should be between 1 (unimportant) and 10 (important)
            $rating: If your articles are e. g. rated by the users, you can give them a scale, e. g. 5 (for 5 stars, etc): it is of type float with 2 digits after the comma and a maxiumum of 99999,99
        $language is the language for the stop-words (either 'en' or 'de' for Germansmarc7c968)
        */
        //$mysqldate = date('Y-m-d H:i:s');
        if (count($articlestructure['parts'])<1 || count($articlestructure['weight'])<1){die('<br />You have to define an array with article parts in the function addArticle');}
        if (count($articlestructure['parts'])!=count($articlestructure['weight'])) die('<br />The number of items in the array "parts" in the array articlestructure does not match the number of items in the sub-array "weight", but it should!');
        
        $mysqldate = date('Y-m-d H:i:s');
        $result = $this->db->query("SELECT COUNT(*) AS nr FROM in_articles WHERE articleid=".$articlenumber." AND module='".$this->module."' LIMIT 1");
        $count = $result->fetch_object()->nr;
        if ($count!=0){
            $this->errormessage = $this->db->error;
            //If this article already exists, we are exiting
            $this->errormessage .= '<br />There is already an article with number '.$articlenumber.' in the database!'; return false;
        }
    
        $this->db->query("INSERT INTO in_articles (articleid, dateadded, module, rating) VALUES (".$articlenumber.", '".$mysqldate."', '".$this->module."', ".$rating.")");
        $lastarticleid = $this->db->insert_id;
        //Now process the parts of the article and write them to the database
        foreach($articletexts as $key1=>$value1){//Loop through the texts and carry the key to look into the articlestructure later to learn the part name
            $words_temp=array(); // This is the array that will store the words returned from the preg_match. MUst be here to initialize it again
            $words_all = array(); //Associative array, where the array-key is the word and the value is the number of occurences of this word in the text
            $words_stripped = ''; //Has the same word only with special characters
            $soundex = ''; //The soundex code of each word
        
            $string = $value1;
            $string = $this->removeHTML($string); //Get rid of tags and htmlentities
            $string = strtolower($string); //Get everything lowercase.
            //Remove e-mail addresses from the words
            $string = $this->removeEmailURL($string);
            
            $sql_temp = array();
            $word_id = 0; //This stores the ID of the database for a word
            $word_exists = false; //Switch, if a word already exists in the database
            //Split the string in to words with at least 2 characters that are saved in an array
            preg_match_all('/\b\w\w+\b/', $string, $words_temp);
            /*
             * LOOP THROUGH ALL WORDS
             */
            //First, we loop through the array and clean from unwanted words AND it does mysqli_real_escape_string
            $this->removeStopwords($words_temp[0], $language);
            foreach($words_temp[0] as $value2){ //This loop runs through the array and puts them in the new array words_all. There, it saves the word as the key and the number of occurences as the value
                      if (array_key_exists($value2, $words_all))
                    {$words_all[$value2]++;}
                  else $words_all[$this->db->real_escape_string($value2)] = 1; //If the word does not exist in the array yet, we create it and escape it first  
            }
            
            $j = 0;//This will be the counter for creating efficient SQL-statements
    
            end($words_all);
            $end_key = key($words_all); //With this, we get the last elements key of the array
            
            foreach ($words_all as $key3=>$value3){//$key3 contains the word, the value the occurence
                //Get rid of all the special characters and store them separately in the database (word_wo_specials);
                $words_stripped = $this->stripAccents($key3, $language);
                //Now create the soundex value of the word either for German or for English, etc.
                if($language=='de') $soundex = $this->germanphonetic($key3); else $soundex = soundex($words_stripped);
                //1. Check, if word exists in database table words. If not, insert it. Also, update the number of totaloccurrences for this word
                if(!$this->db->query("INSERT INTO in_words (word_orig, word_wo_specials, language, soundex) VALUES ('".$key3."', '".$words_stripped."', '".$language."', '".$soundex."')"))
                    {
                    $word_exists = true;
                    $result = $this->db->query("SELECT wordid FROM in_words WHERE word_orig = '".$key3."' LIMIT 1");
                    $row = $result->fetch_assoc();
                    $word_id = $row['wordid'];
                    $this->db->query("UPDATE in_words SET totaloccurrences=totaloccurrences + ".$words_all[$key3]." WHERE wordid=".$word_id); //This updates the occurence also in the in_words-table, so we have a faster access later during searches
                    }
                else {
                    $word_exists = false;
                    $word_id = $this->db->insert_id;
                    $this->db->query("UPDATE in_words SET totaloccurrences=".$words_all[$key3]." WHERE wordid=".$word_id); //Update and put the occurrence of the word in the table
                    }
                    
                                                
                //2. Insert that into the total_occurrences
                $sql_temp[] = "(".$lastarticleid.", ".$word_id.", ".$words_all[$key3].", '".$articlestructure['parts'][$key1]."', '".$this->module."', ".$articlestructure['weight'][$key1].")";
                   if ($j%20==19 || $key3 == $end_key){//This is done, so a max. of 20 rows are sent to the insert-statement at once or it is dumped, when the end of the array is reached
                        $this->db->query('INSERT INTO in_occurrences (aid, wordid, count, articlepart, module, weight) VALUES '.implode(',', $sql_temp));
                        //$this->db->query('INSERT INTO table (text, category) VALUES '.implode(',', $sql_temp));
                        unset ($sql_temp);
                        $sql_temp = array(); //Empty and renew the array 
                   }
                $j++;
                              
          }
         //Write the rest into the database           
         $this->db->query('INSERT INTO in_occurrences (aid, wordid, count, articlepart, module, weight) VALUES '.implode(',', $sql_temp));

         
      }
      return true;
    }//function addArticle  
    
    /*
     * Function to delete an article from the database and delete all occurrences
     */
    public function deleteArticle($articlenumber){
        $row = array(); 
        $result2 = array();
        //First, we have to subtract the occurrences from the occurrence field in the word-table (in_words, field totaloccurrences)
        $result = $this->db->query("SELECT aid FROM in_articles WHERE articleid =".$articlenumber." AND module ='".$this->module."' LIMIT 1");//First get the unique articleid from this database
        $row = $result->fetch_assoc();
        $aid = $row['aid'];
        
        $row=array();
        $row_temp = array();
        $result = array();
        if (!$result = $this->db->query("SELECT wordid, count FROM in_occurrences WHERE aid=".$aid)){$this->errormessage="The article with the number ".$articlenumber." does not exist!"; return false;}
        while ($row = $result->fetch_assoc()){
              $result2 = $this->db->query("SELECT totaloccurrences, word_orig FROM in_words WHERE wordid=".$row['wordid']." LIMIT 1");
              $row_temp = $result2->fetch_assoc();
              if (($row_temp['totaloccurrences']-$row['count'])<1){ //If the totaloccurrences of this word are 0 or below, 
                    $this->db->query("DELETE FROM in_words WHERE wordid=".$row['wordid']." LIMIT 1");
              }
              else {
                    $this->db->query("UPDATE in_words SET totaloccurrences=".($row_temp['totaloccurrences']-$row['count'])." WHERE wordid=".$row['wordid']." LIMIT 1"); //Subtract the count of the deleted article in each word
              }
              
              
        }
        $this->db->query("DELETE FROM in_articles WHERE aid=".$aid." LIMIT 1");
        $this->db->query("DELETE FROM in_occurrences WHERE aid=".$aid);
        }
    
    /*
     * updateArticle to first delete and then add an article
     */
    public function updateArticle($articlenumber, array $articlestructure, array $articletexts, $language='en', $rating){
        $this->db->query("DELETE FROM in_articles WHERE articleid=".$articlenumber);
        $this->db->query("DELETE FROM in_occurrences WHERE articleid=".$articlenumber);
        if (!$this->addArticle($articlenumber, $articlestructure, $articletexts, $language='en', $rating))
            return false;
        else return true;
    }


    public function fullSearch($searchstring, array &$fullresults, $maxresults=10, $language=''){
          /*
            * $searchstring: the search string with words or incomplete words (separated by space, comma, semikolon)
            * $language: "en" for English, "de" for German or "", if you dont want to specify a language at all
            * $fullresults: This array will be filled with the results.
            * $maxresults: Number of the maximum results to return
            * The fullSearch function takes complete words (one or more), which should be separated by space or comma or semicolon
            * Maximum is 4 words.
            * It should contain the final user data entry of his search (so not search while you write)
            */
        if (!empty($fullresults)) {$this->errormessage = 'The method fullSearch only accepts an empty array!'; return false;}
        if ($maxresults<=0) die('The number of maxresults must not be 0 or below 0!');
        $soundex = '';
        $limitelements = 4; //This is the limit for search words in one search string.
        $words_temp = array();
        $row = array();
        $sql_loose = '';
        $sql_soundex = '';
        $sql_order = '';
        $query_exact = '';
        $query_loose = '';
        //First, split the words
        if (strlen($searchstring)<2) {$this->errormessage='The method fullSearch only works with at least 2 letters'; return false;}
        //if (ctype_digit($searchstring)) {$this->errormessage='The searchWriting function only works with at least 2 letters'; return false;}
        $this->removeHTML($searchstring); //Strip the HTML stuff
        $searchstring = strtolower($searchstring); //Get everything lowercase.
        preg_match_all('/\b\w\w+\b/', $searchstring, $words_temp); //Get the words from the string;
        $this->removeStopwords($words_temp[0], $language);
        $this->shortenArray($words_temp[0], $limitelements); //This is important, because we dont want to search for all the words in the searchstring, but only for a maximum of e. g. 4
        $number_words = sizeof($words_temp[0]); 
        
        foreach($words_temp[0] as $key=>$value){ //Cycle through words
            $word_orig = $this->db->real_escape_string($value);
            $word_wo_specials = $this->stripAccents($word_orig, $language);
            //Get the soundex value for the word
            if($language=='de') $soundex = $this->germanphonetic($word_orig); else $soundex = soundex($word_wo_specials);
            if ($word_orig == $word_wo_specials){  //Only search for one word in this
                $sql_loose = $sql_loose."JOIN
                        (SELECT o".$key.".aid, o".$key.".count, o".$key.".weight
                        FROM in_occurrences o".$key."
                        JOIN in_words w".$key."
                        ON w".$key.".wordid = o".$key.".wordid
                        WHERE w".$key.".word_orig LIKE '".$word_orig."%' AND o".$key.".module = '".$this->module."'
                        ) r".$key."
                        ON a.aid = r".$key.".aid ";
                        
                $sql_soundex = $sql_soundex."JOIN
                        (SELECT o".$key.".aid, o".$key.".count, o".$key.".weight
                        FROM in_occurrences o".$key."
                        JOIN in_words w".$key."
                        ON w".$key.".wordid = o".$key.".wordid
                        WHERE (w".$key.".word_orig LIKE '".$word_orig."%' OR w".$key.".soundex='".$soundex."') AND o".$key.".module = '".$this->module."'
                        ) r".$key."
                        ON a.aid = r".$key.".aid ";        
            } 

            
                       
            else    {                       //ELSE search for the word and the word without special characters
                $sql_loose = $sql_loose."JOIN
                        (SELECT o".$key.".aid
                        FROM in_occurrences o".$key."
                        JOIN in_words w".$key."
                        ON w".$key.".wordid = o".$key.".wordid
                        WHERE (w".$key.".word_orig LIKE '".$word_orig."%' OR w".$key.".word_wo_specials LIKE '".$word_wo_specials."') AND o".$key.".module = '".$this->module."'
                        ) r".$key." ON a.aid = r".$key.".aid ";
                        
                        
                $sql_soundex = $sql_soundex."JOIN
                        (SELECT o".$key.".aid
                        FROM in_occurrences o".$key."
                        JOIN in_words w".$key."
                        ON w".$key.".wordid = o".$key.".wordid
                        WHERE (w".$key.".word_orig LIKE '".$word_orig."%' OR w".$key.".word_wo_specials LIKE '".$word_wo_specials."' OR w".$key.".soundex='".$soundex."') AND o".$key.".module = '".$this->module."'
                        ) r".$key." ON a.aid = r".$key.".aid ";        
                    }                                
             
             if ($this->use_count) {$sql_order = $sql_order."+ r".$key.".weight*10 + r".$key.".count ";}
             else {} 
             
            }
         
            $sql_order = '(a.rating*10)'.$sql_order;
            $query_loose = "SELECT DISTINCT a.articleid AS articleid
                           FROM in_articles a
                           ".$sql_loose." ORDER BY ".$sql_order." LIMIT ".$maxresults.";";
            $query_soundex = "SELECT DISTINCT a.articleid AS articleid
                           FROM in_articles a
                           ".$sql_soundex." ORDER BY ".$sql_order." LIMIT ".$maxresults.";"; 

            $query_exact = str_replace('%', '', $query_loose); //Get rid of the "%" in LIKE, so we have an exact search               
            //Perform search for the exact keywords
            $result = $this->db->query($query_exact);
            if ($result->num_rows<5)
            { //If the exact search did only yield less than 5 search results
                //Perform the search loosely
                $result = $this->db->query($query_loose);
                if ($result->num_rows<5) {
                    $result = $this->db->query($query_soundex);
                       
                    if ($result->num_rows==0) {$this->errormessage='No results found for the query'; return false;}
                }
                
            }
            while ($row = $result->fetch_assoc()){
                $fullresults[] = $row['articleid'];
                }
         return true;
            
    }

    public function searchWriting($searchstring, array &$searchresult_back, $language=''){
           /*
            * $searchstring: the search string with words or incomplete words (separated by space, comma, semikolon)
            * $language: "en" for English, "de" for German or "", if you dont want to specify a language at all
            * $searchresults: This array will be filled with the results. 
            
            * The searchWriting function takes single letters (a minimum of 2 letters)
            * and starts searching with that only for words. This means that it searches in the word
            * table, but not for matching articles to keep performance good. 
            * The function can also take 2 words or more, separated by space or comma or semicolon
            * Maximum is 5 words.
            * At least one letter is required (not only numbers)
            * There are a couple of steps consecutively taken for the search:
            */
        $searchresults = $searchresult_back;
        if (!empty($searchresults)) {$this->errormessage = 'The method searchWriting only accepts an empty array!'; return false;}
        $limitelements = 4; //This is the limit for search words in one search string.
        $words_temp = array();
        $select_from = array();
        $sql_loose = '';
        $sql_soundex = '';
        $row = array();
        $word_keys = array(); //This stores the key-names of the word array that is left after all the stuff. We need that to retrieve the search results.
        //First, split the words
        if (strlen($searchstring)<2) {$this->errormessage='The searchWriting function only works with at least 2 letters'; return false;}
        //UTF8 Decode the javascript/ajax sent string
        if (preg_match('!\S!u', $searchstring)) { //If UTF8 (this is to check)
        $searchstring = utf8_decode($searchstring);
        }

        
        //if (ctype_digit($searchstring)) {$this->errormessage='The searchWriting function only works with at least 2 letters'; return false;}
        $this->removeHTML($searchstring); //Strip the HTML stuff
        $searchstring = strtolower($searchstring); //Get everything lowercase.
        preg_match_all('/\b\w\w+\b/', $searchstring, $words_temp); //Get the words from the string;
        $this->shortenArray($words_temp[0], $limitelements);
        $number_words = sizeof($words_temp[0]); //Get the number of search words to know, which one is the last one. The last will be searched "LIKE%", the rest "LIKE", which is the same as "=" in SQL
        $operator = '%';
        
        
        foreach($words_temp[0] as $key=>$value){ //Cycle through words to make the query sub-strings
            if ($key == $number_words-1) {$operator = '%';}
            $word_keys[] = $key;
            $word_orig = $this->db->real_escape_string($value);
            $word_wo_specials = $this->stripAccents($word_orig, $language);
            if($language=='de') $soundex = $this->germanphonetic($word_orig); else $soundex = soundex($word_wo_specials);
            if ($word_wo_specials == $word_orig){ //If the word without special characters is the same as the original word, we dont need to search for it

                $sql_loose = $sql_loose."JOIN
                        (SELECT w".$key.".word_orig AS word_orig".$key.", o".$key.".aid AS aid".$key." 
                        FROM in_words w".$key."
                        JOIN in_occurrences o".$key."
                        ON w".$key.".wordid = o".$key.".wordid
                        WHERE w".$key.".word_orig LIKE '".$word_orig."%' AND o".$key.".module = '".$this->module."'
                        ) r".$key."
                        ON a.aid = r".$key.".aid".$key.' ';

                $sql_soundex = $sql_soundex."JOIN
                        (SELECT w".$key.".word_orig AS word_orig".$key.", o".$key.".aid AS aid".$key."
                        FROM in_words w".$key."
                        JOIN in_occurrences o".$key."
                        ON w".$key.".wordid = o".$key.".wordid
                        WHERE (w".$key.".word_orig LIKE '".$word_orig."%' OR w".$key.".soundex='".$soundex."') AND o".$key.".module = '".$this->module."'
                        ) r".$key."
                        ON a.aid = r".$key.".aid".$key.' ';
                        
                                    
            }
            else {
                $sql_loose = $sql_loose."JOIN
                        (SELECT w".$key.".word_orig AS word_orig".$key.", o".$key.".aid AS aid".$key."
                        FROM in_words w".$key."
                        JOIN in_occurrences o".$key."
                        ON w".$key.".wordid = o".$key.".wordid
                        WHERE (w".$key.".word_orig LIKE '".$word_orig."%' OR w".$key.".word_wo_specials LIKE '".$word_wo_specials."') AND o".$key.".module = '".$this->module."'
                        ) r".$key."
                        ON a.aid = r".$key.".aid".$key.' ';

                $sql_soundex = $sql_soundex."JOIN
                        (SELECT w".$key.".word_orig AS word_orig".$key.", o".$key.".aid AS aid".$key."
                        FROM in_words w".$key."
                        JOIN in_occurrences o".$key."
                        ON w".$key.".wordid = o".$key.".wordid
                        WHERE (w".$key.".word_orig LIKE '".$word_orig."%' OR w".$key.".soundex='".$soundex."' OR w".$key.".word_wo_specials LIKE '".$word_wo_specials."') AND o".$key.".module = '".$this->module."'
                        ) r".$key."
                        ON a.aid = r".$key.".aid".$key.' ';
                    }
              $select_from[] = 'word_orig'.$key;                 
          }
        
           //Now make the SELECT stuff (select word_orig1, word_orig2,...) 
            if (count($select_from)==1) $select = $select_from[0];
            else $select = implode(', ', $select_from);
            
            $query_loose = "SELECT DISTINCT ".$select." FROM in_articles a
                           ".$sql_loose." LIMIT 7;";
            $query_soundex = "SELECT DISTINCT ".$select." FROM in_articles a
                           ".$sql_soundex." LIMIT 7;";
                        
            
            $query_exact = str_replace('%', '', $query_loose); //Get rid of the "%" in LIKE, so we have an exact search
            //echo $query_soundex;
            //Perform search for the exact keywords
            $result = $this->db->query($query_exact);
            if ($result->num_rows<7)
            { //If the exact search did only yield less than 5 search results
                //Perform the search loosely
                $result = $this->db->query($query_loose);
                if ($result->num_rows<1) {
                    $result = $this->db->query($query_soundex);

                    if ($result->num_rows==0) {$this->errormessage='No results found for the query'; return false;}
                }
            }
            if ($result->num_rows==0) {$this->errormessage='No results found for the query'; return false;}
            $i = 0;
            while ($row = $result->fetch_assoc()){
                foreach ($word_keys as $value){
                     $searchresults[$i] .= utf8_encode($row['word_orig'.$value]).' ';  
                       
                }
               $i++; 
            }
            //If there are less than 7 results, do a soundex search    
            if($result->num_rows<7){
                    $row=array();
                    if($result2 = $this->db->query($query_soundex))
                    {
                        while ($row = $result2->fetch_assoc()){
                            foreach ($word_keys as $value3){
                            $searchresults[$i] .= utf8_encode($row['word_orig'.$value3]).' ';
                            }
                          $i++;  
                        }    
                    }    
            }    
                
            foreach ($searchresults as &$value){
                $value = trim($value);
            }
            $temp = array();
            $temp = array_unique($searchresults);
            foreach ($temp as $value2){
                $searchresult_back[] = $value2;
            }
            
            //Only return 7 hits
            $this->shortenArray($searchresult_back, 7);                            
        
        return true;      
    }


    
    private function removeHTML($string){
    //removes HTML-tags and converts html-entitites to real characters
        return html_entity_decode(strip_tags ($string));
    }
    
      
       
    private function stripAccents($string, $language='en') {
    //The one without umlaute: return strtr(utf8_decode($string), utf8_decode('àáâãçèéêëìíîïñòóôõùúûıÿÀÁÂÃÇÈÉÊËÌÍÎÏÑÒÓÔÕÙÚÛİ'), 'aaaaceeeeiiiinoooouuuyyAAAACEEEEIIIINOOOOUUUY');
    if ($language=='de'){ //If the language is German, get a couple of changes for the word without special characters
        $string = str_replace(array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'), array('ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'), $string);
        return strtr(($string), ('àáâãäçèéêëìíîïñòóôõöùúûüıÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜİ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }
    else
        {return strtr(($string), ('àáâãäçèéêëìíîïñòóôõöùúûüıÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜİ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');}
    
    }
    
    private function shortenArray(array &$array, $limitelements){ 
    //The array is limited to $limitelements number of elements. This is important, so that if a user enters a search string with 10 search
    //words, it is limited to e. g. 4 search words
        $totalcount = sizeof($array);
        if ($totalcount<=$limitelements) return false;
        for ($i=$limitelements; $i<$totalcount; $i++){
            unset($array[$i]);            
        }    
    }
    
    private function removeStopwords(array &$words, $language='en') {
        //This function removes all stopwords in the respective language
        if (!isset($this->stopwords[$language]))die('<br />Unknown language for stop words in function removeStopwords');
        foreach ( $words as $key=>$value ) {
          if (trim($value) == '' || in_array($value, $this->stopwords[$language]) || in_array(str_replace(array('ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'), array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'), $value), $this->stopwords[$language]) || ctype_digit($value)) { //ctype: remove, if it is only number
              unset($words[$key]);
          }
          else{ //If the word was not deleted we now check for badwords
              if(preg_match($this->badwords['en'], $value)>0) unset($words[$key]);
              elseif ($language!='en') 
                    {if(preg_match($this->badwords[$language], $value)>0) unset($words[$key]);}
              
          }
        }
    }
    
    private function removeEmailURL($text)
    {                         
        $text = preg_replace('/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/', '', $text);
        //$text = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/', '', $text);
        return $text;
    }
    

   //TEST
 //echo "<br>ärger = " . germanphonetic("ärger");
 //echo "<br>aerger = " . germanphonetic("aerger");
 //echo "<br>erker = " . germanphonetic("erker");
 //echo "<br>berserker = " . germanphonetic("berserker");
 //exit();

  /**
   * Phonetik für die deutsche Sprache nach dem Kölner Verfahren
   *
   * Die Kölner Phonetik (auch Kölner Verfahren) ist ein phonetischer Algorithmus,
   * der Wörtern nach ihrem Sprachklang eine Zeichenfolge zuordnet, den phonetischen
   * Code. Ziel dieses Verfahrens ist es, gleich klingenden Wörtern den selben Code
   * zuzuordnen, um bei Suchfunktionen eine Ähnlichkeitssuche zu implementieren. Damit
   * ist es beispielsweise möglich, in einer Namensliste Einträge wie "Meier" auch unter
   * anderen Schreibweisen, wie "Maier", "Mayer" oder "Mayr", zu finden.
   *
   * Die Kölner Phonetik ist, im Vergleich zum bekannteren Russell-Soundex-Verfahren,
   * besser auf die deutsche Sprache abgestimmt. Sie wurde 1969 von Postel veröffentlicht.
   *
   * Infos: http://www.uni-koeln.de/phil-fak/phonetik/Lehre/MA-Arbeiten/magister_wilz.pdf
   *
   * Die Umwandlung eines Wortes erfolgt in drei Schritten:
   *
   * 1. buchstabenweise Codierung von links nach rechts entsprechend der Umwandlungstabelle
   * 2. entfernen aller mehrfachen Codes
   * 3. entfernen aller Codes "0" ausser am Anfang
   *
   * Beispiel  Der Name "Müller-Lüdenscheidt" wird folgendermaßen kodiert:
   *
   * 1. buchstabenweise Codierung: 60550750206880022
   * 2. entfernen aller mehrfachen Codes: 6050750206802
   * 3. entfernen aller Codes "0": 65752682
   *
   * Umwandlungstabelle:
   * ============================================
   * Buchstabe      Kontext                  Code
   * -------------  -----------------------  ----
   * A,E,I,J,O,U,Y                            0
   * H                                        -
   * B                                        1
   * P              nicht vor H               1
   * D,T            nicht vor C,S,Z           2
   * F,V,W                                    3
   * P              vor H                     3
   * G,K,Q                                    4
   * C              im Wortanfang
   *                vor A,H,K,L,O,Q,R,U,X     4
   * C              vor A,H,K,O,Q,U,X
   *                ausser nach S,Z           4
   * X              nicht nach C,K,Q         48
   * L                                        5
   * M,N                                      6
   * R                                        7
   * S,Z                                      8
   * C              nach S,Z                  8
   * C              im Wortanfang ausser vor
   *                A,H,K,L,O,Q,R,U,X         8
   * C              nicht vor A,H,K,O,Q,U,X   8
   * D,T            vor C,S,Z                 8
   * X              nach C,K,Q                8
   * --------------------------------------------
   *
   * ---------------------------------------------------------------------
   * Support/Info/Download: http://xlab.x3m.ch oder http://www.x3m.ch
   * ---------------------------------------------------------------------
   *
   * @package    x3m
   * @version    1.2
   * @author     Andy Theiler <andy@x3m.ch>
   * @copyright  Copyright (c) 1996 - 2010, Xtreme Software GmbH, Switzerland (www.x3m.ch)
   * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
   */
   private function germanphonetic($word)
   {
      //echo "<br>input: <b>" . $word . "</b>";

      $code    = "";
      $word    = strtolower($word);

      if (strlen($word) < 1) { return ""; }

      // Umwandlung: v->f, w->f, j->i, y->i, ph->f, ä->a, ö->o, ü->u, ß->ss, é->e, è->e, ê->e, à->a, á->a, â->a, ë->e
      $word = str_replace(array("ç","v","w","j","y","ph","ä","ö","ü","ß","é","è","ê","à","á","â","ë"), array("c","f","f","i","i","f","a","o","u","ss","e","e","e","a","a","a","e"), $word);
      //echo "<br>optimiert1: <b>" . $word . "</b>";

      // Nur Buchstaben (keine Zahlen, keine Sonderzeichen)
      $word = preg_replace('/[^a-zA-Z]/', '', $word);
      //echo "<br>optimiert2: <b>" . $word . "</b>";



      $wordlen = strlen($word);
      $char    = str_split($word);


      // Sonderfälle bei Wortanfang (Anlaut)
      if ($char[0] == 'c')
      {
         // vor a,h,k,l,o,q,r,u,x
         switch ($char[1]) {
            case 'a':
            case 'h':
            case 'k':
            case 'l':
            case 'o':
            case 'q':
            case 'r':
            case 'u':
            case 'x':
               $code = "4";
               break;
            default:
               $code = "8";
               break;
         }
         $x = 1;
      }
      else
      {
         $x = 0;
      }

      for (; $x < $wordlen; $x++)
      {
         switch ($char[$x]) {
            case 'a':
            case 'e':
            case 'i':
            case 'o':
            case 'u':
               $code .= "0";
               break;
            case 'b':
            case 'p':
               $code .= "1";
               break;
            case 'd':
            case 't':
               if ($x+1 < $wordlen) {
                  switch ($char[$x+1]) {
                     case 'c':
                     case 's':
                     case 'z':
                        $code .= "8";
                        break;
                     default:
                        $code .= "2";
                        break;
                  }
               }
               else {
                  $code .= "2";
               }
               break;
            case 'f':
               $code .= "3";
               break;
            case 'g':
            case 'k':
            case 'q':
               $code .= "4";
               break;
            case 'c':
               if ($x+1 < $wordlen) {
                  switch ($char[$x+1]) {
                     case 'a':
                     case 'h':
                     case 'k':
                     case 'o':
                     case 'q':
                     case 'u':
                     case 'x':
                        switch ($char[$x-1]) {
                           case 's':
                           case 'z':
                              $code .= "8";
                              break;
                           default:
                              $code .= "4";
                        }
                        break;
                     default:
                        $code .= "8";
                        break;
                  }
               }
               else {
                  $code .= "8";
               }
               break;
            case 'x':
               if ($x > 0) {
                  switch ($char[$x-1]) {
                     case 'c':
                     case 'k':
                     case 'q':
                        $code .= "8";
                     default:
                        $code .= "48";
                  }
               }
               else {
                  $code .= "48";
               }
               break;
            case 'l':
               $code .= "5";
               break;
            case 'm':
            case 'n':
               $code .= "6";
               break;
            case 'r':
               $code .= "7";
               break;
            case 's':
            case 'z':
               $code .= "8";
               break;
         }

      }
      //echo "<br>code1: <b>" . $code . "</b><br />";

      // entfernen aller Codes "0" ausser am Anfang
      $codelen   = strlen($code);
      $num          = array();
      $num          = str_split($code);
      $phoneticcode = $num[0];


      for ($x = 1; $x < $codelen; $x++)
      {
         if ($num[$x] != "0") {
            $phoneticcode .= $num[$x];
         }
      }

      // Mehrfach Codes entfernen und Rückgabe
      // v1.1 (06.08.2010) Thorsten Gottlob <tgottlob@web.de>
      return preg_replace("/(.)\\1+/", "\\1", $phoneticcode);
   }



}//CLASS CLOSING BRACKETS

    
?>