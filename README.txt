Insearch is a PHP index search class with 3-MySQL tables that will add a search functionality to your website. In order to do that, every article that is added to your website and which should be searchable has to be transmitted to the insearch class by easy functions. An "article" could be a forum post or a new blog entry, etc. Note that insearch will NOT save your article. You have to do that yourself. Insearch only takes the entire article and makes an index from the words within the article.

Also, you tell insearch, which structure your article has. E. g. it may have a title, a content and an author. You add weights to these article parts. E. g. you could give the title the weight 3 and the content the weight 1. Also, you can rank the entire article. E. g., if your site has a review functionality, where users can rate articles (e. g. 1 to 5 stars), then you also tell that insearch.
Insearch will - after a search request - order the articles by the ranking, the weight and the occurrence of the words in an article (although this is weighted only by a tenth of the other two). You can also switch off the occurrence-parameter as you may want to prevent users from writing 100x a certain word in an article in order to get a higher ranking in search results. When you start integrating this class, look into this demo-file (and also the javascript insearch.js for search while writing) how it is done!

Insearch also offers a "search while writing" functionality (thanks to jqueryUI and an ajaxcontroller), which shows up to 7 results while typing.

Insearch has these nice features:

    It automatically creates the 3 insearch tables in your database
    It removes possible MySQL-injections with mysql_real_escape_string
    It removes any HTML tags to prevent scripts
    It removes "stopwords", e. g. "the", "usually", etc
    It removes "badwords", e. g. #*~!# :)
    It supports more than only English: currently, also German in implemented
    It stores the word once in the original form and then in again in a form without special characters. E. g. "é" is also stored as "e" and ö is also stored as "oe".
    You can manage multiple modules with one insearch class (and its 3 database tables): e. g. a forum, a blog and your pages. Thus, users only search in one module at a time.
    It can be used freely at no cost under Affero GNU PUBLIC LICENSE. If the software, in which you want to integrate, is not GPL, you have to inquire about another (paid) licens