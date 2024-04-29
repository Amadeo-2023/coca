<?php

/**
 * This file contains all the classes used by the PHP code created by WebSite X5
 *
 * @category  X5engine
 * @package   X5engine
 * @copyright 2013 - Incomedia Srl
 * @license   Copyright by Incomedia Srl http://incomedia.eu
 * @version   WebSite X5 Professional 10.0.0
 * @link      http://websitex5.com
 */

@session_start();

$imSettings = Array();
$l10n = Array();
$phpError = false;
$ImMailer = new ImSendEmail();

@include_once "imemail.inc.php";		// Email class - Static
@include_once "x5settings.php";			// Basic settings - Dynamically created by WebSite X5
@include_once "blog.inc.php";			// Blog data - Dynamically created by WebSite X5
@include_once "access.inc.php";			// Private area data - Dynamically created by WebSite X5
@include_once "l10n.php";				// Localizations - Dynamically created by WebSite X5
@include_once "search.inc.php" ;		// Search engine data - Dynamically created by WebSite X5



/**
 * Blog class
 * @access public
 */
class imBlog
{

    var $comments;

    /**
     * Format a timestamp
     * 
     * @param string $ts The timestamp
     * 
     * @return string
     */
    function formatTimestamp($ts)
    {
        return date("d/m/Y H:i:s", strtotime($ts));
    }

    /**
     * Show the pagination links
     * 
     * @param string  $baseurl  The base url of the pagination
     * @param integer $start    Start from this page
     * @param integer $length   For this length
     * @param integer $count    Count of the current objects to show
     * 
     * @return void
     */
    function paginate($baseurl = "", $start, $length, $count)
    {
        echo "<div style=\"text-align: center;\">";
        if ($start > 0)
            echo "<a href=\"" . $baseurl . "start=" . ($start - $length) . "&length=" . $length . "\" class=\"imCssLink\">" . l10n("blog_pagination_prev", "&lt;&lt; Newer posts") . "</a>";
        if ($start > 0 && $count > $start + $length)
            echo " | ";
        if ($count > $start + $length)
            echo "<a href=\"" . $baseurl . "start=" . ($start + $length) . "&length=" . $length . "\" class=\"imCssLink\">" . l10n("blog_pagination_next", "Older posts &gt;&gt;") . "</a>";
        echo "</div>";
    }

    /**
     * Provide the page title tag to be shown in the header
     * Keep track of the page using the $_GET vars provided:
     *     - id
     *     - category
     *     - tag
     *     - month
     *     - search
     *
     * @param string $basetitle The base title of the blog, to be appended after the specific page title
     * @param string $separator The separator char, default "-"
     * 
     * @return string The page title
     */
    function pageTitle($basetitle, $separator = "-") {
        global $imSettings;

        if (isset($_GET['id']) && isset($imSettings['blog']['posts'][$_GET['id']])) {
            // Post
            return htmlspecialchars($imSettings['blog']['posts'][$_GET['id']]['title'] . $separator . $basetitle);
        } else if (isset($_GET['category']) && isset($imSettings['blog']['posts_cat'][$_GET['category']])) {
            // Category
            return htmlspecialchars(str_replace("_", " ", $_GET['category']) . $separator . $basetitle);
        } else if (isset($_GET['tag'])) {
            // Tag
            return htmlspecialchars(strip_tags($_GET['tag']) . $separator . $basetitle);
        } else if (isset($_GET['month']) && is_numeric($_GET['month']) && strlen($_GET['month']) == 6) {
            // Month
            return htmlspecialchars(substr($_GET['month'], 4, 2) . "/" . substr($_GET['month'], 0, 4) . $separator . $basetitle);
        } else if (isset($_GET['search'])) {
            // Search
            return htmlspecialchars(strip_tags(urldecode($_GET['search'])) . $separator . $basetitle);
        }
        
        // Default (Home page): Show the blog description
        return htmlspecialchars($basetitle);
    }

    /**
     * Show the page description to be echoed in the metatag description tag.
     * Keep track of the page using the $_GET vars provided:
     *     - id
     *     - category
     *     - tag
     *     - month
     *     - search
     *
     * @return string The required description
     */
    function pageDescription()
    {
        global $imSettings;
        
        if (isset($_GET['id']) && isset($imSettings['blog']['posts'][$_GET['id']])) {
            // Post
            return htmlspecialchars(str_replace("\n", " ", $imSettings['blog']['posts'][$_GET['id']]['summary']));
        } else if (isset($_GET['category'])) {
            // Category
            return htmlspecialchars(strip_tags($_GET['category']));
        } else if (isset($_GET['tag'])) {
            // Tag
            return htmlspecialchars(strip_tags($_GET['tag']));
        } else if (isset($_GET['month'])) {
            // Month
            return htmlspecialchars(substr($_GET['month'], 4, 2) . "/" . substr($_GET['month'], 0, 4));
        } else if (isset($_GET['search'])) {
            // Search
            return htmlspecialchars(strip_tags(urldecode($_GET['search'])));
        }
        
        // Default (Home page): Show the blog description
        return htmlspecialchars(str_replace("\n", " ", $imSettings['blog']['description']));
    }

    /**
     * Get the last update date
     *
     * @return string
     */
    function getLastModified()
    {
        global $imSettings;
        $c = $this->comments->getComments($_GET['id']);
        if ($_GET['id'] != "" && $c != -1) {
            return $this->formatTimestamp($c[count($c)-1]['timestamp']);
        } else {
            $last_post = $imSettings['blog']['posts'];
            $last_post = array_shift($last_post);
            return $last_post['timestamp'];
        }
    }

    /**
     * Show a post
     * 
     * @param string  $id    the post id
     * @param intger  $ext   Set 1 to show as extended
     * @param integer $first Set 1 if this is the first post in the list
     *
     * @return void
     */
    function showPost($id, $ext=0, $first=0)
    {
        global $imSettings;
        
        $bs = $imSettings['blog'];
        $bp = isset($bs['posts'][$id]) ? $bs['posts'][$id] : false;

        if (is_bool($bp) && !$bp)
            return;


        $title = !$ext ? "<a href=\"?id=" . $id . "\">" . $bp['title'] . "</a>" : $bp['title'];
        echo "<h2 id=\"imPgTitle\" style=\"display: block;\">" . $title . "</h2>\n";
        echo "<div class=\"imBreadcrumb\" style=\"display: block;\">" . l10n('blog_published_by') . "<strong> " . $bp['author'] . " </strong>";
        echo l10n('blog_in') . " <a href=\"?category=" . urlencode($bp['category']) . "\" target=\"_blank\" rel=\"nofollow\">" . $bp['category'] . "</a> &middot; " . $bp['timestamp'];

        // Media audio/video
        if (isset($bp['media'])) {
            echo " &middot; <a href=\"" . $bp['media'] . "\">Download " . basename($bp['media']) . "</a>";
        }

        if (count($bp['tag']) > 0) {
            echo "<br />Tags: ";
            for ($i = 0; $i < count($bp['tag']); $i++) {
                echo "<a href=\"?tag=" . $bp['tag'][$i] . "\">" . $bp['tag'][$i] . "</a>";
                if ($i < count($bp['tag']) - 1)
                    echo ",&nbsp;";
            }
        }
        echo "</div>\n";

        if ($ext || $first && $imSettings['blog']['post_type'] == 'firstshown' || $imSettings['blog']['post_type'] == 'allshown') {
            echo "<div class=\"imBlogPostBody\">\n";

            if (isset($bp['mediahtml']) || isset($bp['slideshow'])) {
                // Audio/video
                if (isset($bp['mediahtml'])) {
                    echo $bp['mediahtml'] . "\n";
                }

                // Slideshow
                if (isset($bp['slideshow'])) {
                    echo $bp['slideshow'];
                }
                echo "<div style=\"clear: both; margin-bottom: 10px;\"></div>";
            }
            echo $bp['body'];

            if (count($bp['sources']) > 0) {
                echo "\t<div class=\"imBlogSources\">\n";
                echo "\t\t<b>" . l10n('blog_sources') . "</b>:<br />\n";
                echo "\t\t<ul>\n";

                foreach ($bp['sources'] as $source) {
                    echo "\t\t\t<li>" . $source . "</li>\n";
                }

                echo "\t\t</ul>\n\t</div>\n";
            }
            echo (isset($imSettings['blog']['addThis']) ? "<br />" . $imSettings['blog']['addThis'] : "") . "<br /><br /></div>\n";
        } else {
            echo "<div class=\"imBlogPostSummary\">" . $bp['summary'] . "</div>\n";
        }
        if ($ext == 0) {
            echo "<div class=\"imBlogPostRead\"><a class=\"imCssLink\" href=\"?id=" . $id . "\">" . l10n('blog_read_all') ." &raquo;</a></div>\n";
        } else if (isset($bp['foo_html'])) {
            echo "<div class=\"imBlogPostFooHTML\">" . $bp['foo_html'] . "</div>\n";
        }

        if ($ext != 0 && $bp['comments']) {
            echo "<div id=\"blog-topic\">\n";
            $this->comments = new ImTopic($imSettings['blog']['file_prefix'] . 'pc' . $id, "../", "index.php?id=" . $id);
            // Show the comments
            if ($bs['sendmode'] == "db")
                $this->comments->loadDb($bs['dbhost'], $bs['dbuser'], $bs['dbpassword'], $bs['dbname'], $bs['dbtable']);
            else
                $this->comments->loadXML($bs['folder']);
            $this->comments->setPostUrl("index.php?id=" . $id);
            if ($imSettings['blog']['comment_type'] != "stars") {
                $this->comments->showSummary($bs['comment_type'] != "comment");
                $this->comments->showComments($bs['comment_type'] != "comment", $bs["comments_order"], $bs["abuse"]);
                $this->comments->showForm($bs['comment_type'] != "comment", $bs['captcha'], $bs['moderate'], $bs['email'], "blog", $imSettings['general']['url'] . "/admin/blog.php?category=" . str_replace(" ", "_", $imSettings['blog']['posts'][$id]['category']) . "&post=" . $id);
            } else {
                $this->comments->showRating();
            }
            echo "</div>";
            echo "<script type=\"text/javascript\">x5engine.boot.push('x5engine.topic({ target: \\'#blog-topic\\', scrollbar: false})', false, 6);</script>\n";
        }
    }

    /**
     * Find the posts tagged with tag
     * 
     * @param string $tag The searched tag
     *
     * @return void
     */
    function showTag($tag)
    {
        global $imSettings;
        $start = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 5;

        if (count($imSettings['blog']['posts']) == 0)
            return;
        $bps = array();
        foreach ($imSettings['blog']['posts'] as $id => $post) {
            if (in_array($tag, $post['tag'])) {
                $bps[] = $post;
            }
        }
        $bpsc = count($bps);
        for($i = $start; $i < ($bpsc < $start + $length ? $bpsc : $start + $length); $i++) {
            if ($i > $start)
                echo "<div class=\"imBlogSeparator\"></div>";
            $this->showPost($bps[$i]['id'], 0, ($i == $start ? 1 : 0));
        }
        $this->paginate("?tag=" . $tag . "&", $start, $length, $bpsc);
    }

    /**
     * Find the post in a category
     * 
     * @param strmg $category the category ID
     *
     * @return void
     */
    function showCategory($category)
    {
        global $imSettings;
        $start = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 5;

        $bps = isset($imSettings['blog']['posts_cat'][$category]) ? $imSettings['blog']['posts_cat'][$category] : false;
        if (!is_array($bps))
            return;
        $bpsc = count($bps);
        for($i = $start; $i < ($bpsc < $start + $length ? $bpsc : $start + $length); $i++) {
            if ($i > $start)
                echo "<div class=\"imBlogSeparator\"></div>";
            $this->showPost($bps[$i], 0, ($i == 0 ? 1 : 0));
        }
        $this->paginate("?category=" . $category . "&", $start, $length, $bpsc);
    }

    /**
     * Find the posts of the month
     * 
     * @param string $month The mont
     *
     * @return void
     */
    function showMonth($month)
    {
        global $imSettings;
        $start = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 5;

        $bps = isset($imSettings['blog']['posts_month'][$month]) ? $imSettings['blog']['posts_month'][$month] : false;
        if (!is_array($bps))
            return;
        $bpsc = count($bps);
        for($i = $start; $i < ($bpsc < $start + $length ? $bpsc : $start + $length); $i++) {
            if ($i > $start)
                echo "<div class=\"imBlogSeparator\"></div>";
            $this->showPost($bps[$i], 0, ($i == $start ? 1 : 0));
        }
        $this->paginate("?month=" . $month . "&", $start, $length, $bpsc);
    }

    /**
     * Show the last n posts
     * 
     * @param integer $count the number of posts to show
     *
     * @return void
     */
    function showLast($count)
    {
        global $imSettings;
        $start = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 5;

        $bps = array_keys($imSettings['blog']['posts']);
        if (!is_array($bps))
            return;
        $bpsc = count($bps);
        for($i = $start; $i < ($bpsc < $start + $length ? $bpsc : $start + $length); $i++) {
            if ($i > $start)
                echo "<div class=\"imBlogSeparator\"></div>";
            $this->showPost($bps[$i], 0, ($i == $start ? 1 : 0));
        }
        $this->paginate("?", $start, $length, $bpsc);
    }

    /**
     * Show the search results
     * 
     * @param string $search the search query
     *
     * @return void
     */
    function showSearch($search)
    {
        global $imSettings;
        $start = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 5;

        $bps = array_keys($imSettings['blog']['posts']);
        $j = 0;
        if (is_array($bps)) {
            $bpsc = count($bps);
            $results = array();
            for ($i = $start; $i < $bpsc; $i++) {
                if (stristr($imSettings['blog']['posts'][$bps[$i]]['title'], $search) || stristr($imSettings['blog']['posts'][$bps[$i]]['summary'], $search) || stristr($imSettings['blog']['posts'][$bps[$i]]['body'], $search)) {
                    $results[] = $bps[$i];
                    $j++;
                }
            }
            for ($i = $start; $i < ($j < $start + $length ? $j : $start + $length); $i++) {
                if ($i > $start)
                    echo "<div class=\"imBlogSeparator\"></div>";
                $this->showPost($bps[$i], 0, ($i == $start ? 1 : 0));
            }
            $this->paginate("?search=" . $search . "&", $start, $length, $j);
            if ($j == 0) {
                echo "<div class=\"imBlogEmpty\">Empty search</div>";
            }
        } else {
            echo "<div class=\"imBlogEmpty\">Empty blog</div>";
        }
    }

    /**
     * Show the categories sideblock
     * 
     * @param integer $n The number of categories to show
     *
     * @return void
     */
    function showBlockCategories($n)
    {
        global $imSettings;

        if (is_array($imSettings['blog']['posts_cat'])) {
            $categories = array_keys($imSettings['blog']['posts_cat']);
            array_multisort($categories);
            echo "<ul>";
            for ($i = 0; $i < count($categories) && $i < $n; $i++) {
                $post = $imSettings['blog']['posts'][$imSettings['blog']['posts_cat'][$categories[$i]][0]];
                echo "<li><a href=\"?category=" . urlencode($categories[$i]) . "\">" . $post['category'] . "</a></li>";
            }
            echo "</ul>";
        }
    }

    /**
     * Show the cloud sideblock
     * 
     * @param string $type TAGS or CATEGORY
     *
     * @return void;
     */
    function showBlockCloud($type)
    {
        global $imSettings;

        $max = 0;
        $min_em = 0.95;
        $max_em = 1.25;
        if ($type == "tags") {
            $tags = array();
            foreach ($imSettings['blog']['posts'] as $id => $post) {
                foreach ($post['tag'] as $tag) {
                    if (!isset($tags[$tag]))
                        $tags[$tag] = 1;
                    else
                        $tags[$tag] = $tags[$tag] + 1;
                    if ($tags[$tag] > $max)
                        $max = $tags[$tag];
                }
            }
            if (count($tags) == 0)
                return;

            $tags = shuffleAssoc($tags);
            
            foreach ($tags as $name => $number) {
                $size = number_format(($number/$max * ($max_em - $min_em)) + $min_em, 2, '.', '');
                echo "\t\t\t<span class=\"imBlogCloudItem\" style=\"font-size: " . $size . "em;\">\n";
                echo "\t\t\t\t<a href=\"?tag=" . urlencode($name) . "\" style=\"font-size: " . $size . "em;\">" . $name . "</a>\n";
                echo "\t\t\t</span>\n";
            }
        } else if ($type == "categories") {
            $categories = array();
            foreach ($imSettings['blog']['posts'] as $id => $post) {
                if (!isset($categories[$post['category']]))
                    $categories[$post['category']] = 1;
                else
                    $categories[$post['category']] = $categories[$post['category']] + 1;
                if ($categories[$post['category']] > $max)
                    $max = $categories[$post['category']];
            }
            if (count($categories) == 0)
                return;

            $categories = shuffleAssoc($categories);

            foreach ($categories as $name => $number) {
                $size = number_format(($number/$max * ($max_em - $min_em)) + $min_em, 2, '.', '');
                echo "\t\t\t<span class=\"imBlogCloudItem\" style=\"font-size: " . $size . "em;\">\n";
                echo "\t\t\t\t<a href=\"?category=" . urlencode($name) . "\" style=\"font-size: " . $size . "em;\">" . $name . "</a>\n";
                echo "\t\t\t</span>\n";
            }
        }
    }

    /**
     * Show the month sideblock
     * 
     * @param integer $n Number of entries
     *
     * @return void
     */
    function showBlockMonths($n)
    {
        global $imSettings;

        if (is_array($imSettings['blog']['posts_month'])) {
            $months = array_keys($imSettings['blog']['posts_month']);
            array_multisort($months, SORT_DESC);
            echo "<ul>";
            for ($i = 0; $i < count($months) && $i < $n; $i++) {
                echo "<li><a href=\"?month=" . $months[$i] . "\">" . substr($months[$i], 4) . "/" . substr($months[$i], 0, 4) . "</a></li>";
            }
            echo "</ul>";
        }
    }

    /**
     * Show the last posts block
     * 
     * @param integer $n The number of post to show
     *
     * @return void
     */
    function showBlockLast($n)
    {
        global $imSettings;

        if (is_array($imSettings['blog']['posts'])) {
            echo "<ul>";
            for ($i = 0; $i < count($imSettings['blog']['posts']) && $i < $n; $i++) {
                $post = array_keys($imSettings['blog']['posts']);
                $post = $imSettings['blog']['posts'][$post[$i]];
                echo "<li><a href=\"?id=" . $post['id'] . "\">" . $post['title'] . "</a></li>";
            }
            echo "</ul>";
        }
    }
}



/**
 * Captcha handling class
 * @access public
 */
class imCaptcha {

    /**
     * Show the captcha chars
     */
    function show($sCode)
    {
        global $oNameList;
        global $oCharList;

        $text = "<!DOCTYPE HTML>
            <html>
          <head>
          <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">
          <meta http-equiv=\"pragma\" content=\"no-cache\">
          <meta http-equiv=\"cache-control\" content=\"no-cache, must-revalidate\">
          <meta http-equiv=\"expires\" content=\"0\">
          <meta http-equiv=\"last-modified\" content=\"\">
          </head>
          <body style=\"margin: 0; padding: 0; border-collapse: collapse;\">";

        for ($i=0; $i<strlen($sCode); $i++)
            $text .= "<img style=\"margin:0; padding:0; border: 0; border-collapse: collapse; width: 24px; height: 24px; position: absolute; top: 0; left: " . (24 * $i) . "px;\" src=\"imcpa_".$oNameList[substr($sCode, $i, 1)].".gif\" width=\"24\" height=\"24\">";

        $text .= "</body></html>";

        return $text;
    }

    /**
     * Check the sent data
     * @param sCode The correct code (string)
     * @param dans The user's answer (string)
     */
    function check($sCode, $ans)
    {
        global $oCharList;
        if ($ans == "")
            return '-1';
        for ($i=0; $i<strlen($sCode); $i++)
          if ($oCharList[substr(strtoupper($sCode), $i, 1)] != substr(strtoupper($ans), $i, 1))
            return '-1';
        return '0';
    }
}



/**
 * Provide support for sending and saving a cart order as well as checking the coupon codes
 */
class ImCart {

    /**
     * Contains the coupon data structured as follows:
     * "cart" => array(
     *        "coupon" => "CARTCOUPON",
     *        "amount" => 10
     *    ),
     *    "products" => array(
     *        "COUPONFORPROD1" => "prod1",
     *        "COUPONFORPROD2" => "prod2",
     *        "COUPONFORPROD3" => "prod1"
     *    )
     *    
     * @var Array
     */
    var $couponData;

    /**
     * Maximum coupon requests every 60 seconds. This is set to prevent the coupon spoofing.
     * Set to 0 to disable the requests limit.
     * 
     * @var integer
     */
    var $couponRequestsPerMinute = 0;

    /**
     * The public folder path used to store the anti-spoofing log to protect the coupon codes
     * 
     * @var string
     */
    var $publicFolder;

    /**
     * Save the order data with the following structure:
     *    "orderNo": "",
     *    "userInvoiceData": {},
     *    "userShippingData": {},
     *    "shipping": {
     *        "name": "",
     *        "description": "",
     *        "email_text": "",
     *        "price": "",
     *        "rawPrice":
     *        "vat":""
     *        "rawVat":
     *    },
     *    "payment": {
     *        "name": "",
     *        "description":"",
     *        "price": "",
     *        "rawPrice":""
     *        "email_text": "",
     *        "vat": "",
     *        "rawVat":""
     *        "html": ""
     *    },
     *    "products": [{
     *        "name": "",
     *        "description": "",
     *        "option": "",
     *        "suboption": "",
     *        "rawSinglePrice": "",
     *        "rawPrice": "",
     *        "rawFullPrice": "",
     *        "singlePrice": "",
     *        "singleFullPrice": "",
     *        "price": "",
     *        "fullPrice": "",
     *        "quantity": "",
     *        "vat": ""
     *    }],
     *    "rawTotalPrice": "",
     *    "rawTalVat": "",
     *    "totalPrice": "",
     *    "totalVat": "",
     *    "coupon": "",
     *    "currency": ""
     * @var array
     */
    var $orderData;

    /**
     * Database connection
     * @var boolean
     */
    var $db = false;
    var $table_prefix = "";

    /**
     * Contains the cart settings
     * @var array
     */
    var $settings = array(
        'orders_table' => 'orders',
        'shipping_addresses_table' => 'shipping_addresses',
        'invoice_addresses_table' => 'invoice_addresses',
        'products_table' => 'products',
        'force_sender' => false,
        'email_opening' => '',
        'email_closing' => '',
        'useCSV' => false,
        'header_bg_color' => '#FFD34F',
        'header_text_color' => '#404040',
        'cell_bg_color' => '#FFFFFF',
        'cell_text_color' => '#000000',
        'border_color' => '#D3D3D3',
        'owner_email' => '',
        'vat_type' => 'none'
    );

    function setSettings($data)
    {
        // Copy the settings preserving the default data when a key is missing
        foreach ($data as $key => $value)
            $this->settings[$key] = $value;
    }

    function setOrderData($data)
    {
        // Sanitize the form data
        if (isset($data['userInvoiceData'])) {
            foreach ($data['userInvoiceData'] as $key => $value) {
                if (isset($value['value']) && isset($value['label'])) {
                    $data['userInvoiceData'][$key] = array(
                        "label" => strip_tags($value['label']),
                        "value" => strip_tags($value['value'])
                    );
                }
            }
        }
        if (isset($data['userShippingData'])) {
            foreach ($data['userShippingData'] as $key => $value) {
                if (isset($value['value']) && isset($value['label'])) {
                    $data['userShippingData'][$key] = array(
                        "label" => strip_tags($value['label']),
                        "value" => strip_tags($value['value'])
                    );
                }
            }
        }
        // As some servers escape the double quotes in the HTML code sent via POST,
        // let's check if we have to unescape the payment HTML code using a test HTML sent by JS
        // Do just 3 tries to avoid unwanted loops
        $i = 3;
        while (isset($data['payment']['htmlCheck']) && $i-- > 0 && $data['payment']['htmlCheck'] != "<a href=\"http://google.it\">escapecheck</a>") {
            $data['payment']['htmlCheck'] = stripcslashes($data['payment']['htmlCheck']);
            $data['payment']['html'] = stripcslashes($data['payment']['html']);
        }
        $this->orderData = $data;
    }

    function setCouponData($data)
    {
        $this->couponData = $data;
    }

    function setPublicFolder($path)
    {
        $this->publicFolder = $path;
    }
   
    /**
     * Check if the current IP can use a coupon code without spoofing
     * 
     * @return boolean
     */
    function canCheckCoupon() {
        $path = "../" . $this->publicFolder . "/nospoof.txt";
        if (!isset($_SERVER['REMOTE_ADDR']) || !@file_exists($path))
            return true;
        foreach (explode("\r\n", @file_get_contents($path)) as $line) {
            $columns = explode("|", $line);
            if (count($columns) == 3 && $columns[0] == $_SERVER['REMOTE_ADDR'] && strtotime($columns[1]) > time() - 60 && $columns[2] * 1 >= $this->couponRequestsPerMinute)
                return false;
        }
        return true;
    }

    /**
     * Save the current user fingerprint. Keeps track of the requests number for each IP address.
     * 
     * @return void
     */
    function saveFingerPrint() {
        if (!isset($_SERVER['REMOTE_ADDR']))
            return;

        $path = "../" . $this->publicFolder . "/nospoof.txt";
        $content = "";
        $old_content = "";
        // Remove the old data from the file to avoid it to become too large
        if (@file_exists($path))
            $old_content = @file_get_contents($path);
        $found = false;
        foreach (explode("\r\n", $old_content) as $line) {
            if (strlen($line) !== 0) {
                $columns = explode("|", $line);
                // If the line contains the current IP, let's check the last request date
                if ($columns[0] == $_SERVER['REMOTE_ADDR']) {
                    if (strtotime($columns[1]) < time() - 60)
                        $columns[2] = 0;
                    $content .= $_SERVER['REMOTE_ADDR'] . "|" . date("Y-m-d H:i:s") . "|" . (($columns[2] * 1) + 1) . "\r\n";
                    $found = true;
                } else if (strtotime($columns[1]) > time() - 60) {
                    // Otherwise, let's save the entry only if it's recent
                    $content .= $columns . "\r\n";
                }
            }
        }
        if (!$found)
            $content .= $_SERVER['REMOTE_ADDR'] . "|" . date('Y-m-d H:i:s') . "|1" . "\r\n";
        @file_put_contents($path,  $content);
    }

    /**
     * Provide the discount data of a coupon code
     * 
     * @param  string $coupon The coupon code
     * 
     * @return string         The data in JSON format
     */
    function checkCoupon($coupon)
    {
        // Avoid spoofing by allowing only 6 tries in 1 minute
        if ($this->couponRequestsPerMinute !== 0) {
            if (!$this->canCheckCoupon())
                return "false";
            $this->saveFingerPrint();
        }

        $coupon = trim($coupon);

        if (!is_array($this->couponData))
            return "false";

        // Check the cart coupon
        if (isset($this->couponData['cart']) && $this->couponData['cart']['coupon'] == $coupon)
            return '{ "type": "cart", "amount": ' . number_format($this->couponData['cart']['amount'], 4, '.', '') . ' }';

        // Check the products coupon
        if (isset($this->couponData['products'])) {
            $products = array();
            foreach ($this->couponData['products'] as $productId => $productCoupon)
                if ($productCoupon == $coupon)
                    $products[] = '"' . $productId . '"';
            if (count($products))
                return '{ "type": "product", "ids": [' . implode(", ", $products) . '] }';    
        }

        // No coupon!
        return "false";
    }

    /**
     * Send the order email
     * 
     * @param boolean $isOwner true to send the owner's email
     * @param string $from from address
     * @param string $to to address
     * 
     * @return boolean
     */
    function sendEmail($isOwner, $from, $to)
    {
        
        global $ImMailer;

        $separationLine = "<tr><td colspan=\"2\" style=\"margin: 10px 0; height: 10px; font-size: 0.1px; border-bottom: 1px solid [email:emailBackground];\">&nbsp;</td></tr>\n";
        $opt = 0;
        $vat = 0;
        $userDataTxt = "";
        $userDataHtml = "";
        $userDataCSVH = Array();
        $userDataCSV = Array();
        $shippingDataTxt = "";
        $shippingDataHtml = "";
        $shippingDataCSVH = Array();
        $shippingDataCSV = Array();
        $orderDataTxt = "";
        $orderDataHTML = "";
        $orderDataCSV = "";

        //
        //    Set the invoice data
        //
        if (isset($this->orderData['userInvoiceData']) && is_array($this->orderData['userInvoiceData'])) {
            $i = 0;
            foreach ($this->orderData['userInvoiceData'] as $key => $value) {
                if (trim($value['value']) != "") {
                    // Is it an email?
                    if (preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])' . '(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i', $value['value'])) {
                        $f = "\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $value['label']) . ":</b></td>\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><a href=\"mailto:" . $value['value'] . "\">". $value['value'] . "</a></td>";
                    } else if (preg_match('/^http[s]?:\/\/[a-zA-Z0-9\.\-]{2,}\.[a-zA-Z]{2,}/', $value['value'])) {
                        // Is it an URL?
                        $f = "\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $value['label']) . ":</b></td>\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><a href=\"" . $value['value'] . "\">". $value['value'] . "</a></td>";
                    } else {
                        $f = "\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $value['label']) . ":</b></td>\n\t\t\t\t<td style=\"[email:contentFontFamily]\">" . str_replace(array("\\'", '\\"'), array("'", '"'), $value['value']) . "</td>";
                    }

                    $userDataTxt .= $value['label'] . ": " . $value['value'] . "\n";
                    $userDataHtml .= "\n\t\t\t<tr" . ($i%2 ? " bgcolor=\"[email:bodyBackgroundOdd]\"" : "") . ">" . $f . "\n\t\t\t</tr>";
                    $userDataCSVH[] = $value['label'];
                    $userDataCSV[] = $value['value'];
                    $i++;
                }
            }
            if ($userDataHtml != "")
                $userDataHtml = "\n\t\t<table width=\"100%\" style=\"[email:contentStyle]\">" . $userDataHtml . "\n\t\t</table>";
        }

        //
        //    Set the shipping data
        //
        if (isset($this->orderData['userShippingData']) && is_array($this->orderData['userShippingData'])) {
            $i = 0;
            foreach ($this->orderData['userShippingData'] as $key => $value) {
                if (trim($value['value']) != "") {
                    // Is it an email?
                    if (preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])' . '(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i', $value['value'])) {
                        $f = "\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $value['label']) . ":</b></td>\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><a href=\"mailto:" . $value['value'] . "\">". $value['value'] . "</a></td>";
                    } else if (preg_match('/^http[s]?:\/\/[a-zA-Z0-9\.\-]{2,}\.[a-zA-Z]{2,}/', $value['value'])) {
                        // Is it an URL?
                        $f = "\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $value['label']) . ":</b></td>\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><a href=\"" . $value['value'] . "\">". $value['value'] . "</a></td>";
                    } else {
                        $f = "\n\t\t\t\t<td style=\"[email:contentFontFamily]\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $value['label']) . ":</b></td>\n\t\t\t\t<td style=\"[email:contentFontFamily]\">" . str_replace(array("\\'", '\\"'), array("'", '"'), $value['value']) . "</td>";
                    }

                    $shippingDataTxt .= $value['label'] . ": " . $value['value'] . "\n";
                    $shippingDataHtml .= "\n\t\t\t<tr" . ($i%2 ? " bgcolor=\"[email:bodyBackgroundOdd]\"" : "") . ">" . $f . "\n\t\t\t</tr>";
                    $shippingDataCSVH[] = $value['label'];
                    $shippingDataCSV[] = $value['value'];
                    $i++;
                }
            }
            if ($shippingDataHtml != "")
                $shippingDataHtml = "\n\t\t<table width=\"100%\" style=\"[email:contentStyle]\">" . $shippingDataHtml . "\n\t\t</table>";
        }
        $userDataCSV = @implode(";", $userDataCSVH) . "\n" . @implode(";", $userDataCSV);
        $shippingDataCSV = @implode(";", $shippingDataCSVH) . "\n" . @implode(";", $shippingDataCSV);
        
        //
        //    Set the products data
        //
        if (isset($this->orderData['products']) && is_array($this->orderData['products'])) {
            $i = 0;
            foreach ($this->orderData['products'] as $key => $value)
                $opt = (isset($value["option"]) && $value["option"] != "null" && strlen($value["option"]));
            $vat = ($this->settings['vat_type'] != "none");
            $colspan = 3 + ($opt ? 1 : 0) + ($vat ? 1 : 0);
            $orderDataHTML = "<table cellpadding=\"5\" width=\"100%\" style=\"[email:contentStyle] border-collapse: collapse; \">
                                <tr bgcolor=\"[email:bodyBackgroundOdd]\">
                                    <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder];\">
                                        <b>" . l10n("cart_name") . "</b>
                                    </td>" .
                                    ($opt ?
                                        "<td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; min-width: 80px;\">
                                            <b>" . l10n("product_option") . "</b>
                                        </td>" : "") .
                                    "<td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder];\">
                                        <b>" . l10n("cart_qty") . "</b>
                                    </td>
                                    <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; min-width: 70px;\">
                                        <b>" . l10n("cart_price") . "</b>
                                    </td>" .
                                    ($vat ?
                                        "<td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; min-width: 70px;\">
                                            <b>" . ($this->settings['vat_type'] == "included" ? l10n("cart_vat_included") : l10n("cart_vat")) ."</b>
                                        </td>" : "") .
                                    "<td  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder];\">
                                        <b>" . l10n("cart_subtot") . "</b>
                                    </td>
                                </tr>\n";

            $orderDataCSV = l10n("cart_name") . ";" . l10n("cart_descr") . ";" . ($opt ? l10n("product_option") . ";" : "") . l10n("cart_qty") . ";" . l10n("cart_price") . ";" . ($vat ? l10n("cart_vat") .";" : "") . l10n("cart_subtot");
            foreach ($this->orderData['products'] as $key => $value) {
                // CSV
                $orderDataCSV .= "\n" 
                . strip_tags(str_replace(array("\n", "\r"), "", $value["name"])) . ";" 
                . strip_tags(str_replace(array("\n", "\r"), "", $value["description"])) . ";" 
                . (($opt && $value["option"] != "null") ? $value["option"] . ( $value["suboption"] != "null" ? " " . $value["suboption"] . ";" : "") : "")
                . $value["quantity"] . ";"
                . ($this->settings['vat_type'] == "excluded" ? $value["singlePrice"] : $value['singlePricePlusVat']) . ";"
                . ($vat ? $value["vat"] .";" : "")
                . ($this->settings['vat_type'] == "excluded" ? $value["price"] : $value['pricePlusVat']);

                // Txt
                $orderDataTxt .= 
                strip_tags(str_replace(array("\n", "\r"), "", $value["name"]))
                . (($opt && $value["option"] != "null") ? " " . $value["option"] . ( $value["suboption"] != "null" ? " " . $value["suboption"] : "") : "")
                . "- " . strip_tags(str_replace(array("\n", "\r"), "", $value["description"])) . "\n "
                . $value["quantity"] . " x " 
                . ( $this->settings['vat_type'] == "excluded" ?
                    "(" . $value["singlePrice"] . " + " . l10n("cart_vat") . " " . $value["vat"] . ")"
                :
                    $value["singlePricePlusVat"]
                )
                . " = " . ($this->settings['vat_type'] == "excluded" ? $value["price"] : $value['pricePlusVat']) . "\n\n";

                // HTML
                $orderDataHTML .= "\n\t\t\t\t<tr valign=\"top\" style=\"[email:contentFontFamily] vertical-align: top\"" . ($i%2 ? " bgcolor=\"#EEEEEE\"" : "") . ">
                                                <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder];\">" . $value["name"] . "<br />" . $value["description"] . "</td>" .
                                                ($opt ? "<td style=\"border: 1px solid [email:bodyBackgroundBorder];\">" . (($value["option"] != "null") ? $value["option"] . (($value["suboption"] != "null") ? " " . $value["suboption"] : "") : "") . "</td>" : "") .
                                                "<td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $value["quantity"] . "</td>" .
                                                ($this->settings['vat_type'] == "excluded" ? 
                                                    "<td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . ($value["singlePrice"] == $value['singleFullPrice'] ? $value['singlePrice'] : $value['singlePrice'] . ' <span style="text-decoration: line-through;">' . $value['singleFullPrice'] . "</span>") . "</td>"
                                                :
                                                    "<td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . ($value["singlePricePlusVat"] == $value['singleFullPricePlusVat'] ? $value['singlePricePlusVat'] : $value['singlePricePlusVat'] . ' <span style="text-decoration: line-through;">' . $value['singleFullPricePlusVat'] . "</span>") . "</td>"
                                                )
                                                 .
                                                ($vat ? "<td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $value["vat"] ."</td>" : "") .
                                                "<td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . ($this->settings['vat_type'] == "excluded" ? $value["price"] : $value['pricePlusVat']) . "</td>
                                            </tr>\n";
                $i++;
            }
        }

        //
        //    Shipping
        //
        if (isset($this->orderData['shipping']) && is_array($this->orderData['shipping'])) {
            $orderDataHTML .= "\n\t\t\t<tr>" . 
                                        ($this->settings['vat_type'] != "none" ?
                                            "<td colspan=\"" . ($colspan - 1) . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . l10n('cart_shipping') . ": " . $this->orderData['shipping']['name'] . "</td>" .
                                            "<td  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $this->orderData['shipping']['vat'] . "</td>"
                                        :
                                            "<td colspan=\"" . $colspan . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . l10n('cart_shipping') . ": " . $this->orderData['shipping']['name'] . "</td>"
                                        )
                                        . "<td  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . ($this->settings['vat_type'] == "excluded" ? $this->orderData['shipping']['price'] : $this->orderData['shipping']['pricePlusVat']) . "</td>
                                    </tr>";
            $orderDataTxt .= "\n" . l10n('cart_shipping') . " - " . $this->orderData['shipping']['name'] . ": " . ($this->settings['vat_type'] == "excluded" ? $this->orderData['shipping']['price'] : $this->orderData['shipping']['pricePlusVat']);
        }

        //
        //    Payment
        //
        if (isset($this->orderData['payment']) && is_array($this->orderData['payment'])) {
            $orderDataHTML .= "\n\t\t\t<tr>" . 
                                        ($this->settings['vat_type'] != "none" ?
                                            "<td colspan=\"" . ($colspan - 1) . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . l10n('cart_payment') . ": " . $this->orderData['payment']['name'] . "</td>" .
                                            "<td  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $this->orderData['payment']['vat'] . "</td>"
                                        :
                                            "<td colspan=\"" . $colspan . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . l10n('cart_payment') . ": " . $this->orderData['payment']['name'] . "</td>"
                                        )
                                        . "<td  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . ($this->settings['vat_type'] == "excluded" ? $this->orderData['payment']['price'] : $this->orderData['payment']['pricePlusVat']) . "</td>
                                    </tr>";
            $orderDataTxt .= "\n" . l10n('cart_payment') . " - " . $this->orderData['payment']['name'] . ": " . ($this->settings['vat_type'] == "excluded" ? $this->orderData['payment']['price'] : $this->orderData['payment']['pricePlusVat']);
        }

        //
        //  Coupon
        //
        if (isset($this->orderData['coupon']) && $this->orderData['coupon'] !== "") {
            $orderDataHTML .= "\n\t\t\t<tr>
                                    <td colspan=\"" . $colspan . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . l10n('cart_coupon', "Coupon Code") . "</td>
                                    <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $this->orderData['coupon'] . "</td>
                                </tr>";
            $orderDataTxt .= "\n" . l10n('cart_coupon', "Coupon Code")  . ": " . $this->orderData['coupon'];      
        }

        //
        //  Total amounts
        //
        switch ($this->settings['vat_type']) {
            case "excluded":
                $orderDataHTML .= "\n\t\t\t<tr>
                                        <td colspan=\"" . $colspan . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right; font-weight: bold;\">" . l10n('cart_vat') . "</td>
                                        <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $this->orderData['totalVat'] . "</td>
                                    </tr>";
                $orderDataHTML .= "\n\t\t\t<tr>
                                        <td colspan=\"" . $colspan . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right; font-weight: bold;\">" . l10n('cart_total_vat') . "</td>
                                        <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $this->orderData['totalPricePlusVat'] . "</td>
                                    </tr>";
                $orderDataTxt .= "\n" . l10n('cart_vat')  . ": " . $this->orderData['totalVat'];
                $orderDataTxt .= "\n" . l10n('cart_total_vat')  . ": " . $this->orderData['totalPricePlusVat'];
            break;
            case "included": 
                $orderDataHTML .= "\n\t\t\t<tr>
                                        <td colspan=\"" . $colspan . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right; font-weight: bold;\">" . l10n('cart_total_vat') . "</td>
                                        <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $this->orderData['totalPricePlusVat'] . "</td>
                                    </tr>";
                $orderDataHTML .= "\n\t\t\t<tr>
                                        <td colspan=\"" . $colspan . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right; font-weight: bold;\">" . l10n('cart_vat_included') . "</td>
                                        <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $this->orderData['totalVat'] . "</td>
                                    </tr>";
                $orderDataTxt .= "\n" . l10n('cart_total_vat')  . ": " . $this->orderData['totalPricePlusVat'];
                $orderDataTxt .= "\n" . l10n('cart_vat_included')  . ": " . $this->orderData['totalVat'];
            break;
            case "none":
                $orderDataHTML .= "\n\t\t\t<tr>
                                        <td colspan=\"" . $colspan . "\"  style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right; font-weight: bold;\">" . l10n('cart_total_price') . "</td>
                                        <td style=\"[email:contentFontFamily] border: 1px solid [email:bodyBackgroundBorder]; text-align: right;\">" . $this->orderData['totalPricePlusVat'] . "</td>
                                    </tr>";
                $orderDataTxt .= "\n" . l10n('cart_total_price')  . ": " . $this->orderData['totalPricePlusVat'];
            break;
        }
        $orderDataHTML .= "</table>";

        $txtMsg = "";
        $htmlMsg = "<table border=0 width=\"100%\" style=\"[email:contentStyle]\">\n";

        //
        // Order number
        // 
        $htmlMsg .= "<tr><td colspan=\"2\" style=\"[email:contentFontFamily] text-align: center; font-weight: bold;font-size: 1.11em\">" . l10n('cart_order_no') . ": " . $this->orderData['orderNo'] . "</td></tr>";
        $txtMsg .= str_replace("<br>", "\n", l10n('cart_order_no') . ": " . $this->orderData['orderNo']);

        //
        //  Opening message
        //
        if (!$isOwner) {
            $htmlMsg .= "\n<tr><td colspan=\"2\">" . $this->settings['email_opening'] . "</td></tr>";
            $txtMsg .= "\n\n" . strip_tags(str_replace("<br />", "\n", $this->settings['email_opening']));
        }

        // Customer's data
        if ($shippingDataHtml != "") {
            $htmlMsg .= "\n<tr style=\"vertical-align: top\" valign=\"top\">\n\t<td style=\"[email:contentFontFamily] width: 50%; padding: 20px 0;\">\n\t\t<h3 style=\"font-size: 1.11em\">" . l10n('cart_vat_address') . "</h3>\n\t\t" . $userDataHtml . "\n\t</td>";
            $htmlMsg .= "\n\t<td style=\"[email:contentFontFamily] width: 50%; padding: 20px 0;\">\n\t\t<h3 style=\"font-size: 1.11em\">" . l10n('cart_shipping_address') . "</h3>\n\t\t" . $shippingDataHtml . "</td>\n\t</tr>";

            $txtMsg .= "\n" . str_replace("<br />", "\n", l10n('cart_vat_address') . "\n" . $userDataTxt);
            $txtMsg .= "\n" . str_replace("<br />", "\n", l10n('cart_shipping_address') . "\n" . $shippingDataTxt);
        } else {
            $htmlMsg .= "\n<tr>\n\t<td colspan=\"2\" style=\"[email:contentFontFamily] padding: 20px 0 0 0;\">\n\t\t<h3 style=\"font-size: 1.11em\">" . l10n('cart_vat_address') . "/" . l10n('cart_shipping_address') . "</h3>\n\t\t" . $userDataHtml . "</td>\n</tr>";
            $txtMsg .= "\n" . str_replace("<br />", "\n", l10n('cart_vat_address') . "/" . l10n('cart_shipping_address') . "\n" . $userDataTxt);
        }

        $htmlMsg .= $separationLine;

        // Products
        $htmlMsg .=  "<tr><td colspan=\"2\" style=\"padding: 5px 0 0 0;\"><h3 style=\"font-size: 1.11em\">" . l10n('cart_product_list') . "</h3>" . $orderDataHTML . "</td></tr>";
        $txtMsg .= "\n\n" . str_replace("<br />", "\n", l10n('cart_product_list') . "\n" . $orderDataTxt);

        $htmlMsg .= $separationLine;

        // Payment
        if (isset($this->orderData['payment']) && is_array($this->orderData['payment'])) {
            $htmlMsg .= "<tr>
                            <td colspan=\"2\" style=\"padding: 5px 0 0 0;\">
                                <h3 style=\"font-size: 1.11em\">" . l10n('cart_payment') . "</h3>" .
                                nl2br($this->orderData['payment']['name']) .( !$isOwner ? '<br />' . nl2br($this->orderData['payment']['email_text']) . ($this->orderData['payment']['html'] != "" ? '<div style="text-align: center; margin-top: 20px;">' . $this->orderData['payment']['html'] . '</div>' : "") : "" ) .
                            "</td>
                        </tr>";
            $txtMsg .= "\n\n" . str_replace("<br />", "\n", l10n('cart_payment') . "\n" . $this->orderData['payment']['name'] . ( !$isOwner ? "\n" . $this->orderData['payment']['email_text'] : "" ));

            $htmlMsg .= $separationLine;
        }

        // Shipping
        if (isset($this->orderData['shipping']) && is_array($this->orderData['shipping'])) {
            $htmlMsg .= "<tr>
                            <td colspan=\"2\" style=\"padding: 5px 0 0 0;\">
                                <h3 style=\"font-size: 1.11em\">" . l10n('cart_shipping') . "</h3>" .
                                nl2br($this->orderData['shipping']['name']) . ( !$isOwner ? '<br />' . nl2br($this->orderData['shipping']['email_text']) : "" ) .
                            "</td>
                        </tr>";
            $txtMsg .= "\n\n" . str_replace("<br />", "\n", l10n('cart_shipping') . "\n" . $this->orderData['shipping']['name'] . ( !$isOwner ? "\n" . $this->orderData['shipping']['email_text'] : "" ));
        }

        //
        //  Closing message
        //
        if (!$isOwner) {
            $htmlMsg .= $separationLine;
            $htmlMsg .= "\n<tr><td colspan=\"2\" style=\"padding: 5px 0 0 0;\">" . $this->settings['email_closing'] . "</td></tr>";
            $txtMsg .= "\n\n" . strip_tags(str_replace("<br />", "\n", $this->settings['email_closing']));
        }
        
        // Close the html table
        $htmlMsg .= "</table>\n";

        $attachments = array();
        if ($this->settings['useCSV'] && $isOwner) {
            $txtMsg .= $userDataCSV . "\n" . $orderDataCSV;
            $attachments[] = array("name" => "user_data.csv", "content" => $userDataCSV, "mime" => "text/csv");
            $attachments[] = array("name" => "order_data.csv", "content" => $orderDataCSV, "mime" => "text/csv");
        }
        return $ImMailer->send($from, $to, l10n('cart_order_no') . " " . $this->orderData['orderNo'], $txtMsg, $htmlMsg, $attachments);
    }

    /**
     * Send the order email to the owner
     * @return boolean
     */
    function sendOwnerEmail()
    {
        return $this->sendEmail(true, $this->settings['owner_email'], $this->settings['owner_email']);
    }

    /**
     * Send the order email to the customer
     * @return boolean
     */
    function sendCustomerEmail()
    {
        return $this->sendEmail(false, $this->settings['owner_email'], $this->orderData['userInvoiceData']['Email']['value']);
    }

    
    /**
     * Set the database connection data
     * 
     * @param String $host        
     * @param String $user        
     * @param String $pwd         
     * @param String $db          
     * @param String $table_prefix
     *
     * @return Boolean
     */
    function setDatabaseConnection($host, $user, $pwd, $db, $table_prefix)
    {
        $this->table_prefix = $table_prefix;
        $this->db = new ImDb($host, $user, $pwd, $db);
        if ($this->db->testConnection())
            return true;
        $this->db = false;
        return false;
    }

    /**
     * Close the database connection
     * 
     * @return void
     */
    function closeDatabaseConnection()
    {
        if ($this->db)
            $this->db->closeConnection();
    }

    /**
     * Save the order to DB
     * 
     * @return boolean
     */
    function saveOrderToDB()
    {
        if (!$this->db)
            return false;
        // Create the tables
        if (!$this->db->tableExists($this->table_prefix . "orders")) {
            $this->db->createTable(
                $this->table_prefix . $this->settings['orders_table'],
                array(
                    "id" => array('type' => 'VARCHAR(16)', 'primary' => true),
                    "ts" => array('type' => 'TIMESTAMP'),
                    "ip" => array('type' => 'VARCHAR(16)'),
                    "price" => array('type' => 'FLOAT'),
                    "vat" => array('type' => 'FLOAT'),
                    "price_plus_vat" => array('type' => 'FLOAT'),
                    "currency" => array('type' => 'VARCHAR(4)'),
                    "shipping_name" => array('type' => 'VARCHAR(32)'),
                    "shipping_price" => array('type' => 'FLOAT'),
                    "shipping_vat" => array('type' => 'FLOAT'),
                    "shipping_price_plus_vat" => array('type' => 'FLOAT'),
                    "payment_name" => array('type' => 'VARCHAR(32)'),
                    "payment_price" => array('type' => 'FLOAT'),
                    "payment_vat" => array('type' => 'FLOAT'),
                    "payment_price_plus_vat" => array('type' => 'FLOAT'),
                    "coupon" => array("type" => "VARCHAR(32)"),
                    "vat_type" => array("type" => "VARCHAR(8)")
                )
            );
            $this->db->createTable(
                $this->table_prefix . $this->settings['products_table'],
                array(
                    "order_id" => array('type' => 'VARCHAR(16)', 'primary' => true),
                    "product_id" => array('type' => 'VARCHAR(16)', 'primary' => true),
                    "option" => array('type' => 'VARCHAR(32)', 'primary' => true),
                    "suboption" => array('type' => 'VARCHAR(32)', 'primary' => true),
                    "price" => array('type' => 'FLOAT'),
                    "vat" => array('type' => 'FLOAT'),
                    "price_plus_vat" => array('type' => 'FLOAT'),
                    "quantity" => array('type' => 'INT(11)'),
                    "name" => array('type' => 'TEXT')
                )
            );
            $this->db->createTable(
                $this->table_prefix . $this->settings['invoice_addresses_table'],
                array(
                    "order_id" => array('type' => 'VARCHAR(16)', 'primary' => true),
                    "label" => array('type' => 'VARCHAR(32)', 'primary' => true),
                    "index" => array('type' => 'INT(11)'),
                    "value" => array('type' => 'TEXT')
                )
            );
            $this->db->createTable(
                $this->table_prefix . $this->settings['shipping_addresses_table'],
                array(
                    "order_id" => array('type' => 'VARCHAR(16)', 'primary' => true),
                    "label" => array('type' => 'VARCHAR(32)', 'primary' => true),
                    "index" => array('type' => 'INT(11)'),
                    "value" => array('type' => 'TEXT')
                )
            );
        }

        // Check if the current order number already exists
        do {
            $res = $this->db->query("SELECT id FROM `" . $this->table_prefix . $this->settings['orders_table'] . "` WHERE id='" . $this->db->escapeString($this->orderData['orderNo']) ."'");
            if (count($res))
                $this->orderData['orderNo'] .= rand(0, 9);
        } while (count($res));

        // Save the order data
        $this->db->query(
            "INSERT INTO `" . $this->table_prefix . $this->settings['orders_table'] . "` "
            . "(id, ts, ip, vat_type, price, vat, price_plus_vat, currency, shipping_name, shipping_price, shipping_vat, shipping_price_plus_vat, payment_name, payment_price, payment_vat, payment_price_plus_vat, coupon) VALUES("
            . "'" . $this->db->escapeString($this->orderData['orderNo']) . "',
            '" . date("Y-m-d H:i:s") . "',
            '" . $_SERVER['REMOTE_ADDR'] . "',
            '" . $this->settings['vat_type'] . "',
            " . $this->orderData['rawTotalPrice'] . ",
            " . $this->orderData['rawTotalVat'] . ",
            " . $this->orderData['rawTotalPricePlusVat'] . ",
            '" . $this->orderData['currency'] . "',
            '" . (isset($this->orderData['shipping']) ? $this->orderData['shipping']['name'] : ''). "',
            " . (isset($this->orderData['shipping']) ? $this->orderData['shipping']['rawPrice'] : 0) . ",
            " . (isset($this->orderData['shipping']) ? $this->orderData['shipping']['rawVat'] : 0) . ",
            " . (isset($this->orderData['shipping']) ? $this->orderData['shipping']['rawPricePlusVat'] : 0) . ",
            '" . (isset($this->orderData['payment']) ? $this->orderData['payment']['name'] : '') . "',
            " . (isset($this->orderData['payment']) ? $this->orderData['payment']['rawPrice'] : 0) . ",
            " . (isset($this->orderData['payment']) ? $this->orderData['payment']['rawVat'] : 0). ",
            " . (isset($this->orderData['payment']) ? $this->orderData['payment']['rawPricePlusVat'] : 0) . ",            
            '" . (isset($this->orderData['coupon']) ? $this->orderData['coupon'] : '') . "'
            )"
        );

        // Save the products
        if (isset($this->orderData['products']) && is_array($this->orderData['products'])) {
            $qdata = array();
            $query = "INSERT INTO `" . $this->table_prefix . $this->settings['products_table']. "` (
                    `order_id`,
                    `product_id`,
                    `option`,
                    `suboption`,
                    `price`,
                    `vat`,
                    `price_plus_vat`,
                    `quantity`,
                    `name`
                ) VALUES";
            foreach ($this->orderData['products'] as $key => $product) {
                $qdata[] = "(
                    '" . $this->db->escapeString($this->orderData['orderNo']) . "',
                    '" . $this->db->escapeString($key) . "',
                    '" . $this->db->escapeString(isset($product['option']) ? $product['option'] : '') . "',
                    '" . $this->db->escapeString(isset($product['suboption']) ? $product['suboption'] : '') . "',
                    " . $product['rawPrice'] . ",
                    " . $product['rawVat'] . ",
                    " . $product['rawPricePlusVat'] . ",
                    " . $product['quantity'] . ",
                    '" . $this->db->escapeString($product['name']) . "'
                )";
            }
            $query .= implode(",", $qdata);
            $this->db->query($query);
        }

        // Save the invoice data
        if (isset($this->orderData['userInvoiceData']) && is_array($this->orderData['userInvoiceData'])) {
            $qdata = array();
            $index = 0;
            $query = "INSERT INTO `" . $this->table_prefix . $this->settings['invoice_addresses_table'] . "` (
                `order_id`,
                `label`,
                `index`,
                `value`
            ) VALUES";
            foreach ($this->orderData['userInvoiceData'] as $field) {
                if ($field['value'] != "") {
                    $qdata[] = "(
                        '" . $this->db->escapeString($this->orderData['orderNo']) . "',
                        '" . $this->db->escapeString($field['label']) . "',
                        '" . $index++ . "',
                        '" . $this->db->escapeString($field['value']) . "'
                    )";
                }
            }
            $query .= implode(",", $qdata);
            $this->db->query($query);
        }

        // Save the shipping data
        if (isset($this->orderData['userShippingData']) && is_array($this->orderData['userShippingData'])) {
            $qdata = array();
            $index = 0;
            $query = "INSERT INTO `" . $this->table_prefix . $this->settings['shipping_addresses_table'] . "` (
                `order_id`,
                `label`,
                `index`,
                `value`
            ) VALUES";
            foreach ($this->orderData['userShippingData'] as $field) {
                if ($field['value'] != "") {
                    $qdata[] = "(
                        '" . $this->db->escapeString($this->orderData['orderNo']) . "',
                        '" . $this->db->escapeString($field['label']) . "',
                        '" . $index++ . "',
                        '" . $this->db->escapeString($field['value']) . "'
                    )";
                }
            }
            $query .= implode(",", $qdata);
            $this->db->query($query);
        }

        return $this->orderData['orderNo'];
    }

    /**
     * Delete an order from the DB
     * @param  String $id
     * @return void              
     */
    function deleteOrderFromDb($id)
    {
        if (!$this->db)
            return false;
        $id = $this->db->escapeString($id);
        $this->db->query("DELETE FROM `" . $this->table_prefix . $this->settings['orders_table'] . "` WHERE `id`='" . $id . "'" );
        $this->db->query("DELETE FROM `" . $this->table_prefix . $this->settings['invoice_addresses_table'] . "` WHERE `order_id`='" . $id . "'" );
        $this->db->query("DELETE FROM `" . $this->table_prefix . $this->settings['shipping_addresses_table'] . "` WHERE `order_id`='" . $id . "'" );
        $this->db->query("DELETE FROM `" . $this->table_prefix . $this->settings['products_table'] . "` WHERE `order_id`='" . $id . "'" );
    }

    /**
     * Get a list of the orders in the DB
     *
     * @param Number $pagination_start
     * @param Number $pagination_length
     * @param String $filter Filter the result matching this string
     * 
     * @return array
     */
    function getOrders($pagination_start, $pagination_length, $filter = "")
    {
        $result = array(
            "orders" => array(),
            "paginationCount" => 0
        );
        if (!$this->db)
            return $result;
        // Search for specific orders
        if (strlen($filter)) {
            $ids = array();
            // Search in the customer's data
            foreach ($this->db->query("SELECT order_id FROM `" . $this->table_prefix . $this->settings['invoice_addresses_table'] . "` WHERE value LIKE '%" . $this->db->escapeString($filter) . "%'") as $order) {
                $ids[] = "'" . $order['order_id'] . "'";
            }
            // Search in the orders's data
            foreach ($this->db->query("SELECT id FROM `" . $this->table_prefix . $this->settings['orders_table'] . "` WHERE `id` LIKE '%" . $this->db->escapeString($filter) . "%'") as $order) {
                $ids[] = "'" . $order['id'] . "'";
            }
            if (count($ids) > 0) {
                $result['orders'] = $this->db->query("SELECT * FROM `" . $this->table_prefix . $this->settings['orders_table'] . "` WHERE id IN (" . implode(",", $ids) . ") ORDER BY `ts` DESC LIMIT " . $pagination_start . ", " . $pagination_length);
                // Set the pagination maximum length
                $ordersCount = $this->db->query("SELECT COUNT(*) AS c FROM `" . $this->table_prefix . $this->settings['orders_table'] . "` WHERE id IN (" . implode(",", $ids) . ")");
                $result['paginationCount'] = $ordersCount[0]['c'];
            }
        } else {
            $results = $this->db->query("SELECT * FROM `" . $this->table_prefix . $this->settings['orders_table'] . "` ORDER BY `ts` DESC LIMIT " . $pagination_start . ", " . $pagination_length);
            if (!is_bool($results)) {
                $result['orders'] = $results;
                // Set the pagination maximum length
                $ordersCount = $this->db->query("SELECT COUNT(*) AS c FROM `" . $this->table_prefix . $this->settings['orders_table'] . "`");
                $result['paginationCount'] = $ordersCount[0]['c'];
            }
        }
        return $result;
    }

    /**
     * Get an order
     * @param  String $id The order ID
     * @return Array      The order data
     */
    function getOrder($id)
    {
        $result = array();
        $order = $this->db->query("SELECT * FROM `" . $this->table_prefix . $this->settings['orders_table'] . "` WHERE id='" . $this->db->escapeString($id) . "'");
        if (!count($order))
            return array();
        $result['order'] = $order[0];
        $result['products'] = $this->db->query("SELECT * FROM `" . $this->table_prefix . $this->settings['products_table'] . "` WHERE order_id='" . $this->db->escapeString($id) . "'");
        $result['invoice']= $this->db->query("SELECT * FROM `" . $this->table_prefix . $this->settings['invoice_addresses_table'] . "` WHERE order_id='" . $this->db->escapeString($id) . "' ORDER BY `index`");
        $result['shipping'] = $this->db->query("SELECT * FROM `" . $this->table_prefix . $this->settings['shipping_addresses_table'] . "` WHERE order_id='" . $this->db->escapeString($id) . "' ORDER BY `index`");
        return $result;
    }

}




/**
 * This file stores the class used to add/remove/edit comments
 *
 * @category X5engine
 * @package  X5engine
 * @license  Copyright by Incomedia http://incomedia.eu
 * @link     http://websitex5.com
 */

/**
 * Use this class to store comments
 * 
 * @category X5engine
 * @package  X5engine
 * @license  Copyright by Incomedia http://incomedia.eu
 * @link     http://websitex5.com
 */
class ImComment
{

    var $comments = array();
    var $error = 0;

    /**
     * Get the comments from a file
     * 
     * @param string $file The source file path
     * 
     * @return void
     */
    function loadFromXML($file)
    {
        if (!file_exists($file)) {
            $this->comments = array();
            return;
        }

        $xmlstring = @file_get_contents($file); 
        if (strpos($xmlstring, "<?xml") !== false) {
            $xml = new imXML();

            // Remove the garbage (needed to avoid loosing comments when the xml string is malformed)
            $xmlstring = preg_replace('/<([0-9]+)>.*<\/\1>/i', '', $xmlstring);
            $xmlstring = preg_replace('/<comment>\s*<\/comment>/i', '', $xmlstring);
            
            $comments = $xml->parse_string($xmlstring);
            if ($comments !== false && is_array($comments)) {
                $tc = array();
                if (!isset($comments['comment'][0]) || !is_array($comments['comment'][0]))
                    $comments['comment'] = array($comments['comment']);
                for ($i = 0; $i < count($comments['comment']); $i++) {
                    foreach ($comments['comment'][$i] as $key => $value) {
                        if ($key == "timestamp" && strpos($value, "-") == 2) {
                            // The v8 and v9 timestamp was inverted. For compatibility, let's convert it to the correct format.
                            // The v10 format is yyyy-mm-dd hh:ii:ss
                            // The v8 and v9 format is dd-mm-yyyy hh:ii:ss
                            $value = preg_replace("/([0-9]{2})\-([0-9]{2})\-([0-9]{4}) ([0-9]{2})\:([0-9]{2})\:([0-9]{2})/", "$3-$2-$1 $4:$5:$6", $value);
                        }
                        $tc[$i][$key] = str_replace(array("\\'", '\\"'), array("'", '"'), htmlspecialchars_decode($value));
                    }
                }
                $this->comments = $tc;
            } else {
                // The comments cannot be retrieved. The XML is jammed.
                // Do a backup copy of the file and then reset the xml.
                // Hashed names ensure that a file is not copied more than once
                $n = $file . "_version_" . md5($xmlstring);
                if (!@file_exists($n))
                    @copy($file, $n);
                $this->comments = array();
            }
        } else {
            $this->loadFromOldFile($file);
        }
    }

    /**
     * Get the comments from a v8 comments file
     * 
     * @param string $file The source file path
     *
     * @return void
     */
    function loadFromOldFile($file)
    {
        if (!@file_exists($file)) {
            $this->comments = array();
            return;
        }
        $f = @file_get_contents($file);
        $f = explode("\n", $f);
        for ($i = 0;$i < count($f)-1; $i += 6) {
            $c[$i/6]['name'] = stripslashes($f[$i]);
            $c[$i/6]['email'] = $f[$i+1];
            $c[$i/6]['url'] = $f[$i+2];
            $c[$i/6]['body'] = stripslashes($f[$i+3]);
            $c[$i/6]['timestamp'] = preg_replace("/([0-9]{2})\-([0-9]{2})\-([0-9]{4}) ([0-9]{2})\:([0-9]{2})\:([0-9]{2})/", "$3-$2-$1 $4:$5:$6", $f[$i+4]);
            $c[$i/6]['approved'] = $f[$i+5];
            $c[$i/6]['rating'] = 0;
        }
        $this->comments = $c;
    }

    /**
     * Save the comments in a xml file
     * 
     * @param string $file The destination file path
     *
     * @return boolean
     */
    function saveToXML($file)
    {
        // If the count is 0, delete the file and exit
        if (count($this->comments) === 0) {
            if (@file_exists($file))
                @unlink($file);
            return true;
        }

        // If the folder doesn't exists, try to create it
        $dir = @dirname($file);
        if ($dir != "" && $dir != "/" && $dir != "." && $dir != "./" && !file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<comments>\n";
        $i = 0;
        foreach ($this->comments as $comment) {
            $txml = "";
            foreach ($comment as $key => $value) {
                // Well formed content only
                if (!preg_match('/[0-9]+/', $key) && in_array(gettype($value), array('string', 'integer', 'double'))) {
                    $code = str_replace(array("\\'", '\\"', "\\\""), array("'", '"', "\""), preg_replace('/[\n\r\t]*/', '', nl2br($value)));
                    $txml .= "\t\t<" . $key . "><![CDATA[" . htmlspecialchars($code) . "]]></" . $key . ">\n";
                }
            }
            if ($txml != "")
                $xml .= "\t<comment>\n" . $txml . "\t</comment>\n";
        }
        $xml .= "</comments>";

        if ((is_writable($file) || !file_exists($file))) {
            if (!$f = @fopen($file, 'w+')) {
                $this->error = -3;
                return false;
            } else {
                if (flock($f, LOCK_EX)) {
                    $locked = 1;
                }

                if (fwrite($f, $xml) === false) {
                    $this->error = -4;
                    return false;
                } else {
                    if($locked)
                        flock($f, LOCK_UN);
                    fclose($f);
                    $this->error = 0;
                    return true;
                }
            }
        } else {
            $this->error = -2;
            return false;
        }
    }


    /**
     * Save the comments in a DB
     * 
     * @param string $host     The host name
     * @param string $user     The user name
     * @param string $password The user password
     * @param string $db       The db name
     * @param string $table    The db table
     * @param string $postid   The post id
     * 
     * @return boolean
     */
    function saveToDb($host, $user, $password, $db, $table, $postid)
    {    
        // TODO: Avoid collisions and simplify update/delete the changed rows instead of deleting and rebuilding the table
        
        $db = new ImDb($host, $user, $password, $db);
        if (!$db->testConnection())
            return false;

        // Delete the comments
        $db->query("DELETE FROM `" . $table . "` WHERE postid='" . $db->escapeString($postid) . "'");

        if (count($this->comments) === 0)
            return true;

        // Create the fields definition
        $fields = array(
            "postid" => array(
                "type" => (is_string($postid) ? "VARCHAR(32)" : "INT(11)"),
                "primary" => true
            ),
            "commentid" => array(
                "type" => "INT(11)",
                "primary" => true
            ),
        );
        // Find the fields of each comment
        foreach ($this->comments as $comment) {
            foreach ($comment as $key => $value) {
                if (!in_array($key, array_keys($fields))) {
                    if ($key == "ts" || $key == "timestamp" || $key == "date")
                        $fields[$key] = array("type" => "TIMESTAMP");
                    else
                        $fields[$key] = array("type" => "TEXT");
                }
            }
        }
        $db->createTable($table, $fields);

        // Resave them
        $i = 0;
        $r = true;
        foreach ($this->comments as $comment) {
            $values = array();
            $keys = array();
            foreach ($comment as $key => $value) {
                $keys[] = $key;
                $values[] = "'" . $db->escapeString($this->filterCode($value, true)) . "'";
            }
            $query = "INSERT INTO `" . $table . "` (postid,commentid," . implode(",", $keys) . ") VALUES ('" . $postid . "'," . $i++ . "," . implode(",", $values) . ")";
            $r = $r && $db->query($query);
            if (!$r)
                echo $db->error() . PHP_EOL;
        }
        $db->closeConnection();
        return $r;
    }

    /**
     * Load the comments from a DB
     * 
     * @param string $host     The host name
     * @param string $user     The user name
     * @param string $password The user password
     * @param string $db       The db name
     * @param string $table    The db table
     * @param string $postid   The post id
     * 
     * @return boolean
     */
    function loadFromDb($host, $user, $password, $db, $table, $postid)
    {
        $db = new ImDb($host, $user, $password, $db);
        if (!$db->testConnection())
            return false;
        $rows = $db->query("SELECT * FROM `" . $table . "` WHERE postid='" . $postid . "'");
        if (is_bool($rows)) {
            $this->comments = array();
            return false;
        }

        foreach ($rows as $row) {
            $comment = array();
            foreach ($row as $key => $value) {
                // Filter some fields
                if ($key != "postid" && $key != "commentid" && !is_numeric($key)) {
                    $comment[$key] = $value;
                }
            }
            $this->comments[] = $comment;
        }
    }


    /**
     * Add a comment to a file
     * 
     * @param array $comment the array of data to store
     *
     * @return void
     */
    function add($comment)
    {
        foreach ($comment as $key => $value) {
            $comment[$key] = $this->filterCode($value, true);
        }
        $this->comments[] = $comment;
    }

    /**
     * Sort the array
     * 
     * @param string $orderby Field to compare when ordering the array
     * @param string $sort    Sort by ascending (asc) or descending (desc) order
     * 
     * @return void         
     */
    function sort($orderby = "", $sort = "desc")
    {
        if (count($this->comments) === 0)
            return;

        // Find where the comments has this field
        // This is useful to order using a field which is not present in every comment (like the ts field, which is missing in the stars-only vote type)
        $comment = null;
        for ($i=0; $i < count($this->comments) && $comment == null; $i++) { 
            if (isset($this->comments[$i][$orderby]))
                $comment = $this->comments[$i];
        }
        if ($comment === null)
            return;
        
        // Order the array
        $symbol = (strtolower($sort) == "desc" ? '<' : '>');
        $compare = 'if (!isset($a["' . $orderby . '"]) || !isset($b["' . $orderby . '"])) return 0;';
        $compare .= 'if($a["' . $orderby . '"] == $b["' . $orderby . '"]) return 0; ';

        // The orderable field is a timestamp
        if (preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2}:[0-9]{2}/", $comment[$orderby]))
            $compare .= 'if (strtotime($a["' . $orderby . '"]) ' . $symbol . ' strtotime($b["' . $orderby . '"])) return -1; return 1;';

        // The orderable field is a number
        else if (is_numeric($comment[$orderby]))
            $compare .= 'if ($a["' . $orderby . '"] ' . $symbol . ' $b["' . $orderby . '"]) return -1; return 1;';

        // The orderable field is a string
        else if (is_string($comment[$orderby]))
            $compare .= 'return strcmp($a["' . $orderby . '"], $b["' . $orderby . '"]);';

        // Sort and return
        usort($this->comments, create_function('$a, $b', $compare));
    }

    /**
     * Get all the comments
     * 
     * @param string $orderby Field to compare when ordering the array
     * @param string $sort    Sort by ascending (asc) or descending (desc) order
     * 
     * @return array
     */
    function getAll($orderby = "", $sort = "desc")
    {
        if ($orderby == "" || count($this->comments) === 0)
            return $this->comments;
        
        $this->sort($orderby, $sort);
        
        return $this->comments;
    }

    /**
     * Get the comment n
     * 
     * @param integer $n The index of comment
     * 
     * @return array
     */
    function get($n)
    {
        if (isset($this->comments[$n]))
            return $this->comments[$n];
        return array();
    }

    /**
     * Replace the comment $n with $comment
     * 
     * @param integer $n       Comment number
     * @param array   $comment Comment data
     * 
     * @return boolean
     */
    function edit($n, $comment)
    {
        if (isset($this->comments[$n])) {
            $this->comments[$n] = $comment;
            return true;
        }
        return false;
    }

    /**
     * Delete the comment at $n
     * 
     * @param integer $n The index of the comment
     * 
     * @return void
     */
    function delete($n)
    {
        // Delete an element from the array and reset the indexes
        if (isset($this->comments[$n])) {
            $comments = $this->comments;
            $this->comments = array();
            for ($i = 0; $i < count($comments); $i++)
                if ($i != $n)
                    $this->comments[] = $comments[$i];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Clean the data from XSS
     * 
     * @param string  $str         The string to parse
     * @param boolean $allow_links true to allow links
     * 
     * @return string
     */
    function filterCode($str, $allow_links = false)
    {
        global $imSettings;

        if (gettype($str) != 'string')
            return "";

        // Remove javascript
        while (($start = imstripos($str, "<script")) !== false) {
            $end = imstripos($str, "</script>") + strlen("</script>");
            $str = substr($str, 0, $start) . substr($str, $end);
        }

        // Remove PHP Code
        while (($start = imstripos($str, "<?")) !== false) {
            $end = imstripos($str, "?>") + strlen("?>");
            $str = substr($str, 0, $start) . substr($str, $end);
        }

        // Remove ASP code
        while (($start = imstripos($str, "<%")) !== false) {
            $end = imstripos($str, "%>") + strlen("<%");
            $str = substr($str, 0, $start) . substr($str, $end);
        }

        // Allow only few tags
        $str = strip_tags($str, '<b><i><u>' . ($allow_links ? '<a>' : ''));
        
        // Remove XML injection code
        while (($start = imstripos($str, "<![CDATA[")) !== false) {    
            // Remove the entire XML block when possible
            if (imstripos($str, "]]>") !== false) {
                $end = imstripos($str, "]]>") + strlen("]]>");
                $str = substr($str, 0, $start) . substr($str, $end);
            } else {        
                $str = str_replace("<![CDATA[", "", str_replace("<![cdata[", "", $str));
            }
        }
        while (($start = imstripos($str, "]]>")) !== false) {
            $str = str_replace("]]>", "", $str);
        }

        // Remove all the onmouseover, onclick etc attributes
        while (($res = preg_replace("/(<[\\s\\S]+) on.*\\=(['\"\"])[\\s\\S]+\\2/i", "\\1", $str)) != $str) {
            // Exit in case of error
            if ($res == null)
                break;
            $str = $res;
        }

        $matches = array();
        preg_match_all('~<a.*>~isU', $str, $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            if (imstripos($matches[0][$i], 'nofollow') === false && imstripos($matches[0][$i], $imSettings['general']['url']) === false) {
                $result = trim($matches[0][$i], ">") . ' rel="nofollow">';
                $str = str_replace(strtolower($matches[0][$i]), strtolower($result), $str);
            }
        }

        return $str;
    }

    /**
     * Provide the last error
     * 
     * @return int
     */
    function lastError()
    {
        return $this->error;
    }
}




class ImDb
{

    var $conn;
    var $db;
    var $db_name;
    var $engine = "MYISAM";

    /**
     * Define a new ImDb Object
     * (PHP5 Constructor)
     * 
     * @param string $host
     * @param string $user
     * @param string $pwd
     * @param string $db
     *
     * @return void
     */
    function __construct($host, $user, $pwd, $db)
    {
        $this->setUp($host, $user, $pwd, $db);
    }

    /**
     * Define a new ImDb Object
     * (PHP4 Constructor)
     * 
     * @param string $host
     * @param string $user
     * @param string $pwd
     * @param string $db
     *
     * @return void
     */ 
    function ImDb($host, $user, $pwd, $db)
    {
        $this->setUp($host, $user, $pwd, $db);
    }

    function setUp($host, $user, $pwd, $db)
    {
        $this->db_name = $db;
        $this->conn = @mysql_connect($host, $user, $pwd);        
        if ($this->conn === false)
            return;
        $this->db = @mysql_select_db($db, $this->conn);
        if ($this->db === false)
            return;
        if (function_exists('mysql_set_charset'))
            @mysql_set_charset("utf8", $this->conn);
        else
            @mysql_query('SET NAMES "utf8"', $this->conn);
    }

    /**
     * Check wheter the class is connected or not
     *
     * @return boolean
     */
    function testConnection()
    {
        return ($this->conn !== false && $this->db !== false);
    }

    /**
     * Close the connection
     * 
     * @return void
     */
    function closeConnection()
    {
        @mysql_close($this->conn);
    }

    /**
     * Create a new table or update an existing one. The $fields array must be passed as:
     * array( "field_name" => array(
     *     "type" => "TEXT"
     *     "null" => true
     *     "more" => "More data"
     * ))
     * @param string $name   The table name
     * @param array $fields  The table fields list
     * @return boolean
     */
    function createTable( $name, $fields )
    {
        $qfields = array();
        $primaries = array();
        $createResult = false;

        // If the table does not exists, create it
        if (!$this->tableExists($name)) {
            $query = "CREATE TABLE `" . $this->db_name . "`.`" . $name . "` (";
            foreach ($fields as $key => $value) {
                $qfields[] = "`" . $key . "` " .
                            $value['type'] .
                            ($value['type'] == 'TEXT' ? " CHARACTER SET utf8 COLLATE utf8_unicode_ci" : "") .
                            (!isset($value['null']) || !$value['null'] ? " NOT NULL" : "") .
                            (isset($value['auto_increment']) ? " AUTO_INCREMENT" : "") .
                            (isset($value['more']) ? " " . $value['more'] : "");
                if (isset($value['primary']) && $value['primary']) {
                    $primaries[] = "`" . $key . "`";
                }
            }
            $query .= implode(",", $qfields);
            if (count($primaries))
                $query .= ", PRIMARY KEY (" . implode(",", $primaries) . ")";
            $query .= ") ENGINE = " . $this->engine . " ;";
            $createResult = mysql_query($query, $this->conn);
        } else {
            $result = mysql_query("SHOW COLUMNS FROM `" . $this->db_name . "`.`" . $name . "`", $this->conn);
            if ($result) {
                // Actual fields
                $query = "ALTER TABLE `" . $this->db_name. "`.`" . $name . "`";
                $act_fields = array();
                while ($row = mysql_fetch_array($result))
                    $act_fields[] = $row[0];
                // New fields
                $new_fields = array_diff(array_keys($fields), $act_fields);
                $new_fields = array_merge($new_fields); // Order the indexes
                if (count($new_fields) > 0) {
                    foreach ($new_fields as $key) {
                        $qfields[] = " ADD `" . $key . "` " . $fields[$key]['type'] . 
                        ($fields[$key]['type'] == 'TEXT' ? " CHARACTER SET utf8 COLLATE utf8_unicode_ci" : "") .
                        (!isset($fields[$key]['null']) || !$fields[$key]['null'] ? " NOT NULL" : "") .
                        (isset($value['auto_increment']) ? " AUTO_INCREMENT" : "") .
                        (isset($fields[$key]['more']) ? " " . $fields[$key]['more'] : "");
                    }
                    $query .= implode(",", $qfields);
                    $createResult = mysql_query($query, $this->conn);
                }
            }
        }
        return $createResult;
    }

    /**
     * Delete a table. Used often only for test purposes
     * 
     * @param string $table The table to delete
     * 
     * @return void
     */
    function deleteTable($table)
    {
        mysql_query("DROP TABLE " . $this->db_name . "." . $table, $this->conn);
    }

    /**
     * Check if the table exists in the current database
     * 
     * @param string $table The table name
     * 
     * @return boolean        true if the table exists
     */
    function tableExists($table)
    {
        $result = mysql_query("SHOW FULL TABLES FROM `" . $this->db_name . "` LIKE '" . mysql_real_escape_string($table, $this->conn) . "'", $this->conn);
        // Check that the name is correct (usage of LIKE is not correct if there are wildcards in the table name. Unfortunately MySQL 4 doesn't allow another syntax..)
        while (!is_bool($result) && $tb = mysql_fetch_array($result)) {
            if ($tb[0] == $table)
                return true;
        }
        return false;
    }

    /**
     * Get the last error
     * 
     * @return array
     */
    function error()
    {
        return mysql_error();
    }

    /**
     * Provide the last inserted ID of the AUTOINCREMENT column
     * 
     * @return int The id of the latest insert operation
     */
    function lastInsertId()
    {
        $res = $this->query("SELECT LAST_INSERT_ID() AS `id`");
        if (count($res) > 0 && isset($res[0]['id'])) {
            return $res[0]['id'];
        }
        return 0;
    }

    /**
     * Execute a query
     * 
     * @param string $query The query
     * 
     * @return array        The query result
     */
    function query($query)
    {
        $result = mysql_query($query, $this->conn);
        if (!is_bool($result)) {
            $rows = array();
            while($row = mysql_fetch_array($result))
                $rows[] = $row;
            return $rows;
        }
        return $result;
    }

    /**
     * Escape a string
     * 
     * @param string $string The string to escape
     * 
     * @return string
     */
    function escapeString($string)
    {
        if (!is_array($string)) {
            return mysql_real_escape_string($string, $this->conn);
        } else {
            for ($i = 0; $i < count($string); $i++)
                $string[$i] = $this->escapeString($string[$i]);
            return $string;
        }
    }

    /**
     * Return the number of affected rows in the last query
     * 
     * @return integer
     */
    function affectedRows()
    {
        return mysql_affected_rows($this->conn);
    }
}




class DynamicObject
{
	var $body = "";	
	var $id;
	var $defaultText = "";

	function __construct($id)
	{
		$this->create($id);
	}

	function DynamicObject($id)
	{
		$this->create($id);
	}

	function create($id)
	{
		$this->id = $id;
	}

	function setDefaultText($text)
	{
		$this->defaultText = $text;
	}

	function setContent($content)
	{
		$this->body = $content;
	}

	function getContent()
	{
		if (strlen($this->body))
			return $this->body;
		return $this->defaultText;
	}

	/**
     * Setup the folder
     * 
     * @param string $folder The folder path to prepare
     * 
     * @return string
     */
    function prepFolder($folder)
    {
        if (strlen(trim($folder)) == 0)
            return "./";

        if (substr($folder, 0, -1) != "/")
            $folder .= "/";

        return $folder;
    }

	function loadFromFile($folder)
	{
		$folder = $this->prepFolder($folder);
		if (file_exists($folder . $this->id . ".txt"))
			$this->body = @file_get_contents($folder . $this->id . ".txt");
		else
			$this->body = "";
	}

	function saveToFile($folder)
	{
		$folder = $this->prepFolder($folder);
		if ($folder != "" && $folder != "/" && $folder != "." && $folder != "./" && !file_exists($folder)) {
            @mkdir($folder, 0777, true);
        }
		return @file_put_contents($folder . $this->id . ".txt", $this->body);
	}

	function loadFromDb($host, $user, $password, $db, $table)
	{
		$db = new ImDb($host, $user, $password, $db);
		if (!$db->testConnection())
			return false;
		$data = $db->query("SELECT * FROM `" . $table . "` WHERE id='" . $db->escapeString($this->id) . "'");
		if (is_bool($data))
			return false;
		if (!isset($data[0]['body']))
			return false;
		$this->body = $data[0]['body'];
		return true;
	}

	function saveToDb($host, $user, $password, $db, $table)
	{
		$db = new ImDb($host, $user, $password, $db);
		if (!$db->testConnection())
			return false;
		$db->createTable(
	        $table,
	        array(
	            "id" => array('type' => 'VARCHAR(32)', 'primary' => true),
	            "body" => array('type' => 'TEXT')
	        )
	    );
	    $exists = $db->query("SELECT * FROM `" . $table . "` WHERE id='" . $db->escapeString($this->id) . "'");
	    if ($exists)
	    	return $db->query("UPDATE `" . $table . "` SET body='" . $db->escapeString($this->body) . "' WHERE id='" . $db->escapeString($this->id) . "'");
	    return $db->query("INSERT INTO `" . $table . "` (id, body) VALUES ('" . $db->escapeString($this->id) . "', '" . $db->escapeString($this->body) . "')");
	}	
}




/**
 * Provide support for sending and saving the email form data
 */

class ImForm
{

    var $fields = array();
    var $files = array();
    var $answers = array();

    /**
     * Set the data of a field
     * 
     * @param string $label  The field label
     * @param strin  $value  The field value
     * @param string $dbname The name to use in the db
     * @param boolean $isSeparator True if this field must be used as separator in the email
     * 
     * @return boolean
     */
    function setField($label, $value, $dbname = "", $isSeparator = false)
    {
        $this->fields[] = array(
            "label"     => $label,
            "value"     => $value,
            "dbname"    => $dbname,
            "isSeparator" => $isSeparator
        );
        return true;
    }

    /**
     * Provide the currently set fields
     * 
     * @return array
     */
    function fields()
    {
        return $this->fields;
    }

    /**
     * Set a file field
     * 
     * @param string  $label      The field label
     * @param file    $value      The $_FILE[id] content
     * @param string  $folder     The folder in which the file must be saved
     * @param string  $dbname     The db column in which this filed must be saved
     * @param mixed   $extensions The extensions allowed for the file (string or array)
     * @param integer $maxsize    The max size (0 to not check this)
     * 
     * @param boolean
     */
    function setFile($label, $value, $folder = "", $dbname = "", $extensions = array(), $maxsize = 0)
    {
        if (is_string($extensions))
            $extensions = strlen($extensions) ? explode(",", strtolower($extensions)) : array();

        $exists = file_exists($value['tmp_name']);
        if (!$exists)
            return false;
        
        $fileExtension = strtolower(substr($value['name'], strpos($value['name'], ".") + 1));
        $extension = (count($extensions) == 0 || in_array($fileExtension, $extensions));
        $size = ($maxsize == 0 || $maxsize >= $value['size']);

        if (!$extension || !$size)
            return false;
            
        if ($folder != "" && substr($folder, 0, -1) != "/")
            $folder .= "/";
        $this->files[] = array(
            "value" => $value,
            "label" => $label,
            "dbname" => $dbname,
            "folder" => $folder,
            // Save the file content to set is as available for every istance in the class
            // This because after calling move_uploaded_file the temp file is not available anymore
            "content" => @file_get_contents($value['tmp_name'])
        );
        return true;
    }

    /**
     * Provides the currently set files
     *
     *  @return array
     */
    function files()
    {
        return $this->files;
    }

    /**
     * Set the answer to check
     * 
     * @param string $questionId The question id
     * @param string $answer     The correct answer
     *
     * @return void
     */
    function setAnswer($questionId, $answer)
    {
        $this->answers[$questionId] = $answer;
    }

    /**
     * Check if the answer $answer is correct for question $questionId
     * 
     * @param  string $questionId The question id
     * @param  string $answer     The answer
     * 
     * @return boolean
     */
    function checkAnswer($questionId, $answer)
    {
        $questionId += "";
        return (isset($this->answers[$questionId]) && trim(strtolower($this->answers[$questionId])) == trim(strtolower($answer)));
    }

    /**
     * Provides the currently set answers
     * 
     * @return array
     */
    function answers()
    {
        return $this->answers;
    }

    /**
     * Save the data in the database
     * 
     * @param  string $host
     * @param  string $user
     * @param  string $passwd
     * @param  string $database
     * @param  string $table
     * 
     * @return boolean
     */
    function saveToDb($host, $user, $passwd, $database, $table)
    {
        $db = new ImDb($host, $user, $passwd, $database);
        if (!$db->testConnection())
            return false;

        $fields = array();
        $names = array();
        $values = array();

        $i = 0;
        foreach ($this->fields as $field) {
            if (!$field['isSeparator']) {
                $name = isset($field['dbname']) && $field['dbname'] !== "" ? $field['dbname'] : "field_" . $i++;
                $fields[$name] = array(
                    "type" => "TEXT"
                );
                $names[] = "`" . $name . "`";
                $values[] = "'" . $db->escapeString(is_array($field['value']) ? implode(", ", $field['value']) : $field['value']) . "'";
            }
        }
        $i = 0;
        foreach ($this->files as $file) {
            $fieldname = isset($file['dbname']) && $file['dbname'] !== "" ? $file['dbname'] : "file_" . $i++;
            $filename = $this->findFileName($file['folder'], $file['value']['name']);
            $fields[$fieldname] = array(
                "type" => "TEXT"
            );
            $names[] = "`" . $fieldname . "`";
            $values[] = "'" . $db->escapeString($filename) . "'";
            // Create and check the folder
            $folder = "../";
            if (($pos = strpos($file['folder'], "/")) === 0)
                $file['folder'] = substr($file['folder'], 1);
            $folder .= $file['folder'];
            if ($folder != "../" && !file_exists($folder))
                @mkdir($folder, 0777, true);
            $folder = str_replace("//", "/", $folder .= $filename);
            // Save the file
            @move_uploaded_file($file['value']['tmp_name'], $folder);
        }

        // Create the table
        $db->createTable($table, $fields);

        // Save the fields data
        $query = "INSERT INTO `" . $table . "` (" . implode(",", $names) . ") VALUES (" . implode(",", $values) . ")";
        $db->query($query);
        $db->closeConnection();
        return true;
    }

    /**
     * Find a free filename
     * 
     * @param  string $folder   The folder in which the file is being saved
     * @param  string $tmp_name The filename
     * 
     * @return string           The new name
     */
    function findFileName($folder, $tmp_name)
    {
        $pos = strrpos($tmp_name, ".");
        $ext = ($pos !== false ? substr($tmp_name, $pos) : "");
        $fname = basename($tmp_name, $ext);            
        do {
            $rname = $fname . "_" . rand(0, 10000) .  $ext;
        } while (file_exists($folder . $rname));
        return $rname;
    }

    /**
     * Send the email to the site's owner
     * 
     * @param  string  $from
     * @param  string  $to
     * @param  string  $subject
     * @param  string  $text    The email body
     * @param  boolean $csv     Attach the CSV files?
     * 
     * @return boolean
     */
    function mailToOwner($from, $to, $subject, $text, $csv = false)
    {
        global $ImMailer;

        //Form Data
        $txtData = strip_tags($text);
        if (strlen($txtData))
             $txtData .= "\n\n";
        $htmData = nl2br($text);
        if (strlen($htmData))
            $htmData .= "\n<br><br>\n";
        $htmData .= "<table border=0 width=\"100%\" style=\"[email:contentStyle]\">\r\n";
        $csvHeader = "";
        $csvData = "";
        $firstField = true;
        
        foreach ($this->fields as $field) {
            if ($field['isSeparator']) {
                //
                // This field is a form separator
                // 
                $txtData .= (!$firstField ? "\r\n" : "") . $field['label'] . "\r\n" . str_repeat("=", strlen($field['label'])) . "\r\n";
                $htmData .= "<tr valign=\"top\"><td colspan=\"2\" style=\"" . (!$firstField ? "padding-top: 8px;" : "") . " border-bottom: 1px solid [email:bodySeparatorBorderColor];\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $field['label']) . "</b></td></tr>\r\n";
            } else {
                //
                // This field is a classic form field
                // 
                $label = ($field['label'] != "" ? $field['label'] . ": " : "");
                if (is_array($field['value'])) {
                    $txtData .= $label . implode(", ", $field['value']) . "\r\n";
                    $htmData .= "<tr valign=\"top\"><td width=\"25%\"><b>" . $label . "</b></td><td>" . implode(", ", $field['value']) . "</td></tr>\r\n";
                    if ($csv) {
                        $csvHeader .= $field['label'] . ";";
                        $csvData .= implode(", ", $field['value']) . ";";
                    }
                } else {        
                    $txtData .= $label . $field['value'] . "\r\n";
                    // Is it an email?
                    if (preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])' . '(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i', $field['value'])) {
                        $htmData .= "<tr valign=\"top\"><td width=\"25%\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $label) . "</b></td><td><a href=\"mailto:" . $field['value'] . "\">". $field['value'] . "</a></td></tr>\r\n";
                    } else if (preg_match('/^http[s]?:\/\/[a-zA-Z0-9\.\-]{2,}\.[a-zA-Z]{2,}/', $field['value'])) {
                        // Is it an URL?
                        $htmData .= "<tr valign=\"top\"><td width=\"25%\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $label) . "</b></td><td><a href=\"" . $field['value'] . "\">". $field['value'] . "</a></td></tr>\r\n";
                    } else {
                        $htmData .= "<tr valign=\"top\"><td width=\"25%\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $label) . "</b></td><td>" . str_replace(array("\\'", '\\"'), array("'", '"'), $field['value']) . "</td></tr>\r\n";
                    }
                    if ($csv) {
                        $csvHeader .= str_replace(array("\\'", '\\"'), array("'", '"'), $field['label']) . ";";
                        $csvData .= str_replace(array("\\'", '\\"'), array("'", '"'), $field['value']) . ";";
                    }
                }
            }
            $firstField = false;
        }

        $htmData .= "</table>";
        $attachments = array();
        if ($csv) {
            $attachments[] = array(
                "name" => "form_data.csv", 
                "content" => $csvHeader . "\n" . $csvData,
                "mime" => "text/csv"
            );
        }
        foreach ($this->files as $file) {
            $attachments[] = array(
                'name' => $file['value']['name'],
                'content' => $file['content'],
                'mime' => $file['value']['type']
            );
        }
        return $ImMailer->send($from, $to, $subject, $txtData, $htmData, $attachments);
    }

    /**
     * Send the email to the site's customer
     * 
     * @param  string  $from
     * @param  string  $to
     * @param  string  $subject
     * @param  string  $text    The email body
     * @param  boolean $summary Append the data to the email? (It's not an attachment)
     * 
     * @return boolean
     */
    function mailToCustomer($from, $to, $subject, $text, $csv = false)
    {
        global $ImMailer;

        //Form Data
        $txtData = strip_tags($text);
        if (strlen($txtData))
            $txtData .= "\n\n";
        $htmData = nl2br($text);
        if (strlen($htmData))
            $htmData .= "\n<br><br>\n";
        $csvHeader = "";
        $csvData = "";
        $firstField = true;
        
        if ($csv) {
            $htmData .= "<table border=0 width=\"100%\" style=\"[email:contentStyle]\">\r\n";
            foreach ($this->fields as $field) {
                if ($field['isSeparator']) {
                    //
                    // This field is a form separator
                    // 
                    $txtData .= (!$firstField ? "\r\n" : "") . $field['label'] . "\r\n" . str_repeat("=", strlen($field['label'])) . "\r\n";
                    $htmData .= "<tr valign=\"top\"><td colspan=\"2\" style=\"" . (!$firstField ? "padding-top: 8px;" : "") . " border-bottom: 1px solid [email:bodySeparatorBorderColor];\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $field['label']) . "</b></td></tr>\r\n";
                } else {
                    //
                    // This field is a classic form field
                    // 
                    $label = ($field['label'] != "" ? $field['label'] . ": " : "");
                    if (is_array($field['value'])) {
                        $txtData .= $label . implode(", ", $field['value']) . "\r\n";
                        $htmData .= "<tr valign=\"top\"><td width=\"25%\"><b>" . $label . "</b></td><td>" . implode(", ", $field['value']) . "</td></tr>\r\n";
                        if ($csv) {
                            $csvHeader .= $field['label'] . ";";
                            $csvData .= implode(", ", $field['value']) . ";";
                        }
                    } else {                        
                        $txtData .= $label . $field['value'] . "\r\n";
                        // Is it an email?
                        if (preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])' . '(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i', $field['value'])) {
                            $htmData .= "<tr valign=\"top\"><td width=\"25%\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $label) . "</b></td><td><a href=\"mailto:" . $field['value'] . "\">". $field['value'] . "</a></td></tr>\r\n";
                        } else if (preg_match('/^http[s]?:\/\/[a-zA-Z0-9\.\-]{2,}\.[a-zA-Z]{2,}/', $field['value'])) {
                            // Is it an URL?
                            $htmData .= "<tr valign=\"top\"><td width=\"25%\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $label) . "</b></td><td><a href=\"" . $field['value'] . "\">". $field['value'] . "</a></td></tr>\r\n";
                        } else {
                            $htmData .= "<tr valign=\"top\"><td width=\"25%\"><b>" . str_replace(array("\\'", '\\"'), array("'", '"'), $label) . "</b></td><td>" . str_replace(array("\\'", '\\"'), array("'", '"'), $field['value']) . "</td></tr>\r\n";
                        }
                        if ($csv) {
                            $csvHeader .= str_replace(array("\\'", '\\"'), array("'", '"'), $field['label']) . ";";
                            $csvData .= str_replace(array("\\'", '\\"'), array("'", '"'), $field['value']) . ";";
                        }
                    }
                }
                $firstField = false;
            }
            $htmData .= "</table>\n";
        }
        
        return $ImMailer->send($from, $to, $subject, $txtData, $htmData);
    }
}



/**
 * Private area
 * @access public
 */
class imPrivateArea
{

    var $session_type;
    var $session_uname;
    var $session_uid;
    var $session_gids;
    var $session_page;
    var $cookie_name;
    var $salt;
    var $db = false;
    var $db_table;

    // PHP 5
    function __contruct()
    {
        $this->setup();
    }

    // PHP 4
    function imPrivateArea()
    {
        $this->setup();
    }

    function setup() {
        global $imSettings;

        $this->session_type      = "im_access_utype";
        $this->session_uname     = "im_access_uname";
        $this->session_real_name = "im_access_real_name";
        $this->session_page      = "im_access_request_page";
        $this->session_uid       = "im_access_uid";
        $this->session_gids      = "im_access_gids";
        $this->cookie_name       = "im_access_cookie_uid";
        $this->salt              = $imSettings['general']['salt'];
    }

    /**
     * Encode the string
     * 
     * @param  string $string The string to encode
     * @param  $key The encryption key
     * 
     * @return string    The encoded string
     */
    function _encode($s, $k)
    {
        $r = array();
        for($i = 0; $i < strlen($s); $i++)
            $r[] = ord($s[$i]) + ord($k[$i % strlen($k)]);

        // Try to encode it using base64
        if (function_exists("base64_encode") && function_exists("base64_decode"))
            return base64_encode(implode('.', $r));

        return implode('.', $r);
    }

    /**
     * Decode the string
     * 
     * @param  string $s The string to decode
     * @param  string $k The encryption key
     * @return string    The decoded string
     */
    function _decode($s, $k)
    {

        // Try to decode it using base64
        if (function_exists("base64_encode") && function_exists("base64_decode"))
            $s = base64_decode($s);

        $s = explode(".", $s);
        $r = array();
        for($i = 0; $i < count($s); $i++)
            $r[$i] = chr($s[$i] - ord($k[$i % strlen($k)]));
        return implode('', $r);
    }

    /**
     * Login
     * 
     * @access public
     * 
     * @param uname Username (string)
     * @param pwd Password (string)
     */
    function login($uname, $pwd)
    {
        global $imSettings;

        if (!strlen($uname) || !strlen($pwd))
            return -2;

        // Check if the user exists in the hardcoded file
        if (isset($imSettings['access']['users'][$uname]) && $imSettings['access']['users'][$uname]['password'] == $pwd) {
            $this->_setSession(
                "1",
                $imSettings['access']['users'][$uname]['id'],
                $imSettings['access']['users'][$uname]['groups'],
                $uname,
                $imSettings['access']['users'][$uname]['name']
            );
            return 0;
        }
        // Check if the user exists in the DB and it's validated
        if (!$this->db)
            return -1;
        $user = $this->db->query("SELECT * FROM `" . $this->db_table . "` WHERE BINARY `username`='" . $this->db->escapeString($uname) . "' AND BINARY `password`='" . $this->db->escapeString($pwd) . "'");
        if (!is_array($user) || !count($user))
            return -2;
        if (!$user[0]['validated'])
            return -5;
        $this->_setSession("0", $user[0]['id'], isset($imSettings['access']['webregistrations_gid']) ? array($imSettings['access']['webregistrations_gid']) : array(), $user[0]['username'], $user[0]['realname']);
        return 0;
    }

    /**
     * Set the session after the login
     *
     * @param string $type "0" or "1"
     * @param string $uid
     * @param Array  $gids
     * @param string $uname   
     * @param string $realname
     */
    function _setSession($type, $uid, $gids, $uname, $realname)
    {
        @session_regenerate_id();
        $_SESSION[$this->session_type]      = $this->_encode($type, $this->salt);
        $_SESSION[$this->session_uid]       = $this->_encode($uid, $this->salt);
        $_SESSION[$this->session_uname]     = $this->_encode($uname, $this->salt);
        $_SESSION[$this->session_real_name] = $this->_encode($realname, $this->salt);
        $_SESSION[$this->session_gids]      = $gids;
        $_SESSION['HTTP_USER_AGENT']        = md5($_SERVER['HTTP_USER_AGENT'] . $this->salt);
        @setcookie($this->cookie_name, $this->_encode($uid, $this->salt), 0, "/"); // Expires when the browser is closed
    }

    /**
     * Logout
     * 
     * @access public
     */
    function logout()
    {
        $_SESSION[$this->session_type]  = "";
        $_SESSION[$this->session_uname] = "";
        $_SESSION[$this->session_uid]   = "";
        $_SESSION[$this->session_page]  = "";
        $_SESSION[$this->session_gids]  = array();
        $_SESSION['HTTP_USER_AGENT']    = "";
        @setcookie($this->cookie_name, "", time() - 3600, "/");
        $_COOKIE[$this->cookie_name]    = "";
    }

    /**
     * Save the referrer page
     * 
     * @access public
     */
    function savePage()
    {
        global $imSettings;
        $url = basename($_SERVER['PHP_SELF']);
        if (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']))
            $url .= "?" . $_SERVER['QUERY_STRING'];
        $_SESSION[$this->session_page] = $this->_encode($url, $this->salt);
    }

    /**
     * Return to the referrer page
     * 
     * @access public
     */
    function getSavedPage()
    {
        global $imSettings;
        if (isset($_SESSION[$this->session_page]) && $_SESSION[$this->session_page] != "")
            return $this->_decode($_SESSION[$this->session_page], $this->salt);
        return false;
    }

    /**
     * Get an array of data about the logged user
     * @access public
     */
    function who_is_logged()
    {
        global $imSettings;
        if (isset($_SESSION[$this->session_uname]) && $_SESSION[$this->session_uname] != "" && isset($_SESSION[$this->session_uname])) {
            $uname = $this->_decode($_SESSION[$this->session_uname], $this->salt);
            return array(
                "username" => $uname,
                "uid"      => $this->_decode($_SESSION[$this->session_uid], $this->salt),
                "realname" => $this->_decode($_SESSION[$this->session_real_name], $this->salt),
                "groups"   => $_SESSION[$this->session_gids]
            );
        }
        return false;
    }

    /**
     * Check if the logged user can access to a page
     * 
     * @access public
     * 
     * @param page The page id (string)
     */
    function checkAccess($page)
    {
        global $imSettings;

        //
        // The session can live only in the same browser
        //

        if (!isset($_SESSION[$this->session_type]) || $_SESSION[$this->session_type] == "" || !isset($_SESSION[$this->session_uid]) || $_SESSION[$this->session_uid] == "")
            return -3;

        if (!isset($_SESSION['HTTP_USER_AGENT']) || $_SESSION['HTTP_USER_AGENT'] != md5($_SERVER['HTTP_USER_AGENT'] . $this->salt))
            return -2;

        if ($this->_decode($_SESSION[$this->session_type], $this->salt) == "0" && $_SESSION[$this->session_uid] != "") {
            if (!@in_array($page, $imSettings['access']['waitingpages']))
                return -4;
            return 0;
        }
        $uid = $this->_decode($_SESSION[$this->session_uid], $this->salt);
        if (!@in_array($uid, $imSettings['access']['pages'][$page]) && !@in_array($uid, $imSettings['access']['admins']))
            return -8; // The active user cannot access to this page
        return 0;
    }

    /**
     * Get the user's landing page
     * 
     * @access public
     */
    function getLandingPage()
    {
        global $imSettings;
        if (!isset($_SESSION[$this->session_type]) || !isset($_SESSION[$this->session_uname]) || $_SESSION[$this->session_uname] === '' || !isset($_SESSION[$this->session_uid]) || $_SESSION[$this->session_uid] === '')
            return false;

        if ($this->_decode($_SESSION[$this->session_type], $this->salt) == "0")
            return $imSettings['access']['entrancepage'];

        return $imSettings['access']['users'][$this->_decode($_SESSION[$this->session_uname], $this->salt)]['page'];
    }

    /**
     * Convert a status code to a text message
     * 
     * @param  int $code A status code
     * 
     * @return string
     */
    function messageFromStatusCode($code)
    {
        switch ($code) {

            // Error
            case -8 : return l10n("private_area_account_not_allowed", "Your account is not allowed to access the selected page");
            case -7 : return l10n("private_area_lostpassword_error", "We cannot find your data.");
            case -6 : return l10n("private_area_user_already_exists", "The user already exists.");
            case -5 : return l10n("private_area_not_validated", "Your account is not yet validated.");
            case -4 : return l10n("private_area_waiting", "Your account is not yet active.");
            //case -3 : return l10n("private_area_not_allowed", "A login is required to access this page.");
            case -2 : return l10n("private_area_login_error", "Wrong username or password.");
            case -1 : return l10n("private_area_generic_error", "Generic error.");

            // Success
            case 2  : return l10n('private_area_validation_sent', 'We sent you a validation email.');
            case 3  : return l10n('private_area_registration_success', 'You are now registered.');
            case 4  : return l10n('private_area_lostpassword_success', 'We sent you an email with your password.');

            default : return "";
        }
    }

    /**
     * Redirect in a session safe mode. IIS requires this.
     * 
     * @param  string $to
     * 
     * @return void
     */
    function sessionSafeRedirect($to)
    {
        exit('<!DOCTYPE html><html lang="it" dir="ltr"><head><title>Loading...</title><meta http-equiv="refresh" content="1; url=' . $to . '"></head><body><p style="text-align: center;">Loading...</p></body></html>');
    }

    /**
     * Get the user data
     * @param  String $id The username
     * @return Array The user's data or null if the user is not found
     */
    function getUserByUsername($username)
    {
        global $imSettings;
        
        // Search in the file
        if (isset($imSettings['access']['users'][$username])) {
            $user = $imSettings['access']['users'][$username];
            return array(
                "id"        => $user['id'],
                "ts"        => "",
                "ip"        => "",
                "username"  => $username,
                "password"  => $user['password'],
                "realname"  => $user['name'],
                "email"     => $user['email'],
                "key"       => "",
                "validated" => true,
                "groups"    => $user['groups']
            );
        }
        // Search in the DB
        $res = $this->db->query("SELECT * FROM `" . $this->db_table . "` WHERE `username`='" . $this->db->escapeString($username) . "'");
        if (is_array($res) && count($res) > 0) {
            $user = $res[0];
            return array(
                "id"        => $user['id'],
                "ts"        => $user['ts'],
                "ip"        => $user['ip'],
                "username"  => $user['username'],
                "password"  => $user['password'],
                "realname"  => $user['realname'],
                "email"     => $user['email'],
                "key"       => $user['key'],
                "validated" => $user['validated'],
                "groups"    => array($imSettings['access']['webregistrations_gid'])
            );
        }
        return null;
    }

     
    function getUsersById($ids = array())
    {
        if (is_string($ids)) {
            if (strlen($ids))
                $ids = array($ids);
            else
                $ids = array();
        }

        $res = $this->db->query("SELECT * FROM `" . $this->db_table . "`" . (count($ids) ? " WHERE `id` IN (" . implode(",", $ids) . ")" : ""));
        $users = array();
        if (is_array($res)) {
            foreach ($res as $user) {
                $users[] = array(
                    "id"        => $user['id'],
                    "ts"        => $user['ts'],
                    "ip"        => $user['ip'],
                    "username"  => $user['username'],
                    "password"  => $user['password'],
                    "realname"  => $user['realname'],
                    "email"     => $user['email'],
                    "key"       => $user['key'],
                    "validated" => $user['validated']
                );
            }
        }
        return $users;
    }
    
    /**
     * Setup the db connection
     * 
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbname
     * @param string $dbtable
     */
    function setDBData($host, $username, $password, $dbname, $dbtable)
    {
        $this->db = new ImDb($host, $username, $password, $dbname);
        $this->db_table = $dbtable;
        if (!$this->db->testConnection())
            die("Unable to connect to DB");
    }
    
    /**
     * Get an encoded JSON list of the waiting users' data
     *
     * @param  string $encKey The encryption key used to encode the json data
     * 
     * @return string
     */
    function getWaitingUsers($encKey = "")
    {
        if (!$this->db || !$this->db->tableExists($this->db_table))
            return "";
        $users = array(
            "extra" => array("timestamp" => date("Y-m-d H:i:s")),
            "users" => $this->getUsersById()
        );
        return json_encode($users);
    }

    /**
     * Validate the waiting users listed in $ids. It must be an array of DB ids
     * 
     * @param  array  $dbid
     * 
     * @return bool
     */
    function validateWaitingUserById($dbids = array())
    {
        if (!is_array($dbids))
            $dbids = array($dbids);
        if (!count($dbids))
            return false;
        $this->db->query("UPDATE `" . $this->db_table . "` SET `validated`=1, ts=ts WHERE `validated`=0 AND `id` IN (" . implode(",", $this->db->escapeString($dbids)) . ")");
        return $this->db->affectedRows() > 0;
    }

    /**
     * Validate the waiting users listed in $keys. It must be an array of DB keys.
     * 
     * @param  array $keys
     * @param  boolean $login Automatically login the user if validation is succesful
     * 
     * @return booleal
     */
    function validateWaitingUserByKey($keys = array(), $login = false)
    {
        $user = false;
        if (!is_array($keys))
            $keys = array($keys);

        if ($login && count($keys) == 1) {
            $user = $this->db->query("SELECT `username`, `password` FROM `" . $this->db_table . "` WHERE `key`='" . $this->db->escapeString($keys[0]) . "'");
            if (is_bool($user))
                return false;
            $user = $user[0];
        }
        $this->db->query("UPDATE `" . $this->db_table . "` SET `validated`=1, `ts`=NOW(), `ip`='" . $this->db->escapeString($_SERVER['REMOTE_ADDR']) . "' WHERE `validated`=0 AND `key` IN ('" . implode("','", $this->db->escapeString($keys)) . "')");
        if ($user && $this->db->affectedRows())
            return $this->login($user['username'], $user['password']) == 0;
        return $this->db->affectedRows() > 0;
    }

    /**
     * Remove the remaining waiting users
     *
     * @param  string $ts          Remove only the users registered before this timestamp.
     * @param  array  $usersToKeep Remove all the users but keep the ones listed in this array
     * 
     * @return void
     */
    function removeWaitingUsers($ts, $usersToKeep = array())
    {
        if (!$this->db || !$this->db->tableExists($this->db_table))
            return;
        $query = "DELETE FROM `" . $this->db_table . "` WHERE `ts`<='" . $this->db->escapeString($ts) . "'";
        if (!is_array($usersToKeep))
            $usersToKeep = array($usersToKeep);
        if (count($usersToKeep))
            $query .= " AND id NOT IN (" . implode(",", $this->db->escapeString($usersToKeep)) . ")";
        $this->db->query($query);
    }

    /**
     * Get the validation key of user $dbid
     * 
     * @param  string $dbid
     * 
     * @return string The validation key
     */
    function getKeyFromId($dbid)
    {
        $key = $this->db->query("SELECT `key` FROM `" . $this->db_table . "` WHERE `id`=" . (int)$dbid);
        if (!is_bool($key) && count($key))
            return $key[0]['key'];
        return false;
    }

    /**
     * Create the users table if it doesn't exist
     * 
     * @return void
     */
    function createUsersTable()
    {
        if (!$this->db || $this->db->tableExists($this->db_table))
            return;
        $this->db->createTable(
            $this->db_table,
            array(
                "id"        => array('type' => 'INT(11)', 'primary' => true, 'auto_increment' => true),
                "ts"        => array('type' => 'TIMESTAMP'),
                "ip"        => array('type' => 'VARCHAR(16)'),
                "username"  => array('type' => 'TEXT'),
                "password"  => array('type' => 'TEXT'),
                "realname"  => array('type' => 'TEXT'),
                "email"     => array('type' => 'TEXT'),
                "key"       => array('type' => 'VARCHAR(32)'),
                "validated" => array('type' => 'INT(1)')
            )
        );
    }

    /**
     * Register a new user
     * 
     * @param string $username
     * @param string $password
     * @param string $email   
     * @param string $validated
     * 
     * @return Number the user's ID or the error number (-1: user already exists, -2: generic error)
     */
    function registerNewUser($username, $password, $realname, $email, $validated)
    {
        global $imSettings;

        if (!$this->db)
            return -1;
        if (!strlen($username) || !strlen($password) || !strlen($email))
            return -1;

        $this->createUsersTable();

        // Check if the user already exists in the hardcoded file
        if (isset($imSettings['access']['users'][$username]))
            return -6;

        // Check if the user already exists in the DB as validated user
        if (count($this->db->query("SELECT  `username` FROM `" . $this->db_table . "` WHERE `username`='" . $this->db->escapeString($username) . "' AND `validated`='1'")))
            return -6;

        // Check if the user already exists in the DB as not validated user
        if (count($res = $this->db->query("SELECT  `id`, `username` FROM `" . $this->db_table . "` WHERE `username`='" . $this->db->escapeString($username) . "' AND `validated`='0'")))
            return $res[0]['id'];

        // Create the user's record
        $this->db->query("INSERT INTO `" . $this->db_table . "` (`ts`, `ip`, `username`, `password`, `realname`, `email`, `key`, `validated`) VALUES (" .
            "'" . date("Y-m-d H:i:s") . "'," .
            "'" . $this->db->escapeString($_SERVER['REMOTE_ADDR']) . "'," .
            "'" . $this->db->escapeString($username) . "'," .
            "'" . $this->db->escapeString($password) . "'," .
            "'" . $this->db->escapeString($realname) . "'," .
            "'" . $this->db->escapeString($email) . "'," .
            "'" . md5($username . $password . date("U") . rand(1000, 9999)) . "'," .
            "'" . $this->db->escapeString($validated) . "'" .
        ")");
        return $this->db->lastInsertId();
    }

    /**
     * Notify the registration of a new user
     * 
     * @param  int    $id    
     * @param  string $emailTo
     * 
     * @return void
     */
    function sendNotificationEmail($id, $emailTo)
    {
        global $ImMailer;
        global $imSettings;
        $html = "";

        $user = $this->getUsersById($id);
        $user = $user[0];
        if (is_bool($user) || !count($user))
            return;

        $subject = str_replace("[FIELD]", $imSettings['general']['url'], l10n("private_area_newregistration_subject", "A new user registered to your private area at [FIELD]"));
        $html .= nl2br(str_replace(
                array("[FIELD]", "\n"),
                array($imSettings['general']['url'], "<br />\n"),
                l10n("private_area_newregistration_body", "Here's his data.")
            )) . "<br /><br />\n\n";
        $html .= "<b>" . l10n("private_area_realname", "Name") . ":</b> " . $user['realname'] . "<br />\n";
        $html .= "<b>" . l10n("private_area_username", "Username") . ":</b> " . $user['username'] . "<br />\n";
        $html .= "<b>" . l10n("private_area_email", "Email") . ":</b> " . $user['email'] . "<br />\n";
        $html .= "<b>" . l10n("private_area_ip", "IP") . ":</b> " . $user['ip'] . "<br />\n";
        $html .= "<b>" . l10n("private_area_ts", "Time") . ":</b> " . $user['ts'] . "<br />\n";

        $ImMailer->send(strlen($user['email']) ? $user['email'] : $emailTo, $emailTo, $subject, strip_tags($html), $html);
    }

    /**
     * Send the validation email for the user indentified by $id
     *
     * @param  string $id
     * @param  string $emailFrom [description]
     * 
     * @return void
     */
    function sendValidationEmail($dbid, $emailFrom)
    {
        global $ImMailer;
        global $imSettings;

        $html = "";

        $user = $this->getUsersById($dbid);
        $user = $user[0];
        if (is_bool($user) || !count($user))
            return;

        $subject = str_replace("[FIELD]", $imSettings['general']['url'], l10n("private_area_validation_subject", "Validate your account on [FIELD]"));
        $html .= l10n("private_area_validation_body", "Click here to validate your account:") . " ";
        $html .= "<a href=\"" . $imSettings['general']['url'] . "/imlogin.php?validate=" . $user['key'] . "\">";
        $html .= $imSettings['general']['url'] . "/imlogin.php?validate=" . $user['key'];
        $html .= "</a>";

        $ImMailer->send($emailFrom, $user['email'], $subject, strip_tags($html), $html);
    }

    /**
     * Notify to the admin that this user has lost his password
     * 
     * @param  string $data The user's email or username
     * @param  string $emailFrom
     * 
     * @return boolean
     */
    function sendLostPasswordEmail($data, $emailFrom)
    {
        global $ImMailer;
        global $imSettings;

        $username = false;
        $password = false;
        $emailTo = false;

        $query = "SELECT `email`, `username`, `password` FROM `" . $this->db_table . "` ";
        $query .= "WHERE username='" . $this->db->escapeString($data) ."' OR email='" . $this->db->escapeString($data) ."'";
        $user = $this->db->query($query);
        if (!is_bool($user) && count($user)) {
            $emailTo = $user[0]['email'];
            $username = $user[0]['username'];
            $password = $user[0]['password'];
        } else {
            foreach ($imSettings['access']['users'] as $uname => $user) {
                // If the email identify a user, send the message to him, otherwise send a message to the admin
                if ($uname == $data || $user['email'] == $data) {
                    $emailTo = strlen($user['email']) ? $user['email'] : false;
                    $username = $uname;
                    $password = $user['password'];
                    break;
                }
            }
        }

        if (!$username || !$password)
            return false;

        if (!$emailTo) {
            // Send an email to the admin
            $emailTo = $emailFrom;
            $subject = str_replace("[FIELD]", $imSettings['general']['url'], l10n("private_area_password_recovery_subject_admin", "Password recovery request from [FIELD]"));
            $html = nl2br(str_replace(
                array("[FIELD]", "[URL]", "\n"),
                array($username, $imSettings['general']['url'], "<br />\n"),
                l10n("private_area_password_recovery_body_admin", "The user [FIELD] on [URL] wants to recover his own username and password.")
            ));
        } else {
            // Send an email to the user
            $subject = str_replace("[FIELD]", $imSettings['general']['url'], l10n("private_area_password_recovery_subject_user", "Password recovered for [FIELD]"));
            $html = l10n("private_area_password_recovery_body_user", "Here is your account data: ") . "<br /><br />\n\n";
            $html .= "<b>" . l10n("private_area_username", "Username") . ":</b> " . $username . "<br />\n";
            $html .= "<b>" . l10n("private_area_password", "Password") . ":</b> " . $password . "<br />\n";
        }

        $ImMailer->send($emailFrom, $emailTo, $subject, strip_tags($html), $html);
        return true;
    }

}

/**
 * Contains the methods used by the search engine
 * @access public
 */
class imSearch {

    var $scope;
    var $page;
    var $results_per_page;

    function __construct()
    {
        $this->setScope();
        $this->results_per_page = 10;
    }

    function imSearch()
    {
        $this->setScope();
        $this->results_per_page = 10;
    }

    /**
     * Loads the pages defined in search.inc.php  to the search scope
     * @access public
     */
    function setScope()
    {
        global $imSettings;
        $scope = $imSettings['search']['general']['defaultScope'];

        // Logged users can search in their private pages
        $pa = new imPrivateArea();
        if ($user = $pa->who_is_logged()) {
            foreach ($imSettings['search']['general']['extendedScope'] as $key => $value) {
                if (in_array($user['uid'], $imSettings['access']['pages'][$key]))
                    $scope[] = $value;
            }
        }

        $this->scope = $scope;
    }

    /**
     * Do the pages search
     * @access public
     * @param queries The search query (array)
     */
    function searchPages($queries)
    {
        
        global $imSettings;
        $html = "";
        $found_content = array();
        $found_count = array();

        if (is_array($this->scope)) {
            foreach ($this->scope as $filename) {
                $count = 0;
                $weight = 0;
                $file_content = @implode("\n", file($filename));
                // Replace the nonbreaking space with a white space
                // to avoid that is converted to a 196+160 UTF8 char
                $file_content = str_replace("&nbsp;", " ", $file_content);
                if (function_exists("html_entity_decode"))
                    $file_content = html_entity_decode($file_content, ENT_COMPAT, 'UTF-8');

                // Remove the page menu
                while (stristr($file_content, "<div id=\"imPgMn\"") !== false) {
                    $style_start = imstripos($file_content, "<div id=\"imPgMn\"");
                    $style_end = imstripos($file_content, "</div", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }

                // Remove the breadcrumbs
                while (stristr($file_content, "<div id=\"imBreadcrumb\"") !== false) {
                    $style_start = imstripos($file_content, "<div id=\"imBreadcrumb\"");
                    $style_end = imstripos($file_content, "</div", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }

                // Remove CSS
                while (stristr($file_content, "<style") !== false) {
                    $style_start = imstripos($file_content, "<style");
                    $style_end = imstripos($file_content, "</style", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }

                // Remove JS
                while (stristr($file_content, "<script") !== false) {
                    $style_start = imstripos($file_content, "<script");
                    $style_end = imstripos($file_content, "</script", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }

                // Remove PHP
                while (stristr($file_content, "<?php") !== false) {
                    $style_start = imstripos($file_content, "<?php");
                    $style_end = imstripos($file_content, "?>", $style_start) !== false ? imstripos($file_content, "?>", $style_start) + 2 : strlen($file_content);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }
                $file_title = "";

                // Replace the dynamic objects with their content
                if (is_array($imSettings['search']['dynamicobjects'])) {
                    foreach ($imSettings['search']['dynamicobjects'] as $id => $object) {
                        // Only if the object is in the current scope
                        if ($object['Page'] != $filename)
                            continue;
                        // Load the object's content
                        $dynobj = new DynamicObject($object['ObjectId']);
                        $dynobj->setDefaultText($object['DefaultText']);
                        if (isset($object['Folder'])) {
                            // Load from file
                            $dynobj->loadFromFile(pathCombine(array($imSettings['general']['public_folder'], $object['Folder'])));
                        } else if (isset($object['Database']) && isset($object['Table'])) {
                            // Load from db
                            $db = getDbData($object['Database']);
                            $dynobj->loadFromDb($db['host'], $db['user'], $db['password'], $db['database'], $object['Table']);
                        }
                        // Replace the content
                        $needle_start = "<!-- search-tag " . $object['ObjectId'] . " start -->";
                        $needle_end = "<!-- search-tag " . $object['ObjectId'] . " end -->";
                        $find_start = strpos($file_content, $needle_start);
                        $find_end = strpos($file_content, $needle_end) + strlen($needle_end);
                        $file_content = substr($file_content, 0, $find_start) . $dynobj->getContent() . substr($file_content, $find_end);
                    }
                }
                // Get the title of the page
                preg_match('/\<title\>([^\<]*)\<\/title\>/', $file_content, $matches);
                if (count($matches) > 1)
                    $file_title = $matches[1];
                else {
                    preg_match('/\<h2\>([^\<]*)\<\/h2\>/', $file_content, $matches);
                    if (count($matches) > 1)
                        $file_title = $matches[1];
                }

                if ($file_title != "") {
                    foreach ($queries as $query) {
                        $title = imstrtolower($file_title);
                        while (($title = stristr($title, $query)) !== false) {
                            $weight += 5;
                            $count++;
                            $title = substr($title, strlen($query));
                        }
                    }
                }

                // Get the keywords
                preg_match('/\<meta name\=\"keywords\" content\=\"([^\"]*)\" \/>/', $file_content, $matches);
                if (count($matches) > 1) {
                    $keywords = $matches[1];
                    foreach ($queries as $query) {
                        $tkeywords = imstrtolower($keywords);
                        while (($tkeywords = stristr($tkeywords, $query)) !== false) {
                            $weight += 4;
                            $count++;
                            $tkeywords = substr($tkeywords, strlen($query));
                        }
                    }
                }

                // Get the description
                preg_match('/\<meta name\=\"description\" content\=\"([^\"]*)\" \/>/', $file_content, $matches);
                if (count($matches) > 1) {
                    $keywords = $matches[1];
                    foreach ($queries as $query) {
                        $tkeywords = imstrtolower($keywords);
                        while (($tkeywords = stristr($tkeywords, $query)) !== false) {
                            $weight += 3;
                            $count++;
                            $tkeywords = substr($tkeywords, strlen($query));
                        }
                    }
                }

                // Remove the page title from the result
                while (stristr($file_content, "<h2") !== false) {
                    $style_start = imstripos($file_content, "<h2");
                    $style_end = imstripos($file_content, "</h2", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }

                $page_pos = strpos($file_content, "<div id=\"imContent\">") + strlen("<div id=\"imContent\">");
                $page_end = strpos($file_content, "<div id=\"imBtMn\">");
                if ($page_end == false)
                    $page_end = strpos($file_content, "</body>");
                $file_content = strip_tags(substr($file_content, $page_pos, $page_end-$page_pos));
                $t_file_content = imstrtolower($file_content);

                foreach ($queries as $query) {
                    $file = $t_file_content;
                    while (($file = stristr($file, $query)) !== false) {
                        $count++;
                        $weight++;
                        $file = substr($file, strlen($query));
                    }
                }

                if ($count > 0) {
                    $found_count[$filename] = $count;
                    $found_weight[$filename] = $weight;
                    $found_content[$filename] = $file_content;
                    if ($file_title == "")
                        $found_title[$filename] = $filename;
                    else
                        $found_title[$filename] = $file_title;
                }
            }
        }

        if (count($found_count)) {
            arsort($found_weight);
            $i = 0;
            foreach ($found_weight as $name => $weight) {
                $count = $found_count[$name];
                $i++;
                if (($i > $this->page*$this->results_per_page) && ($i <= ($this->page+1)*$this->results_per_page)) {
                    $title = strip_tags($found_title[$name]);
                    $file = $found_content[$name];
                    $file = strip_tags($file);
                    $ap = 0;
                    $filelen = strlen($file);
                    $text = "";
                    for ($j=0; $j<($count > 6 ? 6 : $count); $j++) {
                        $minpos = $filelen;
                        $word = "";
                        foreach ($queries as $query) {
                            if ($ap < $filelen && ($pos = strpos(strtoupper($file), strtoupper($query), $ap)) !== false) {
                                if ($pos < $minpos) {
                                    $minpos = $pos;
                                    $word = $query;
                                }
                            }
                        }
                        $prev = explode(" ", substr($file, $ap, $minpos-$ap));
                        if (count($prev) > ($ap > 0 ? 9 : 8))
                            $prev = ($ap > 0 ? implode(" ", array_slice($prev, 0, 8)) : "") . " ... " . implode(" ", array_slice($prev, -8));
                        else
                            $prev = implode(" ", $prev);
                        if (strlen($word)) {
                            $text .= $prev . "<strong>" . substr($file, $minpos, strlen($word)) . "</strong>";
                            $ap = $minpos + strlen($word);
                        }
                    }
                    $next = explode(" ", substr($file, $ap));
                    if (count($next) > 9)
                        $text .= implode(" ", array_slice($next, 0, 8)) . "...";
                    else
                        $text .= implode(" ", $next);
                    $text = str_replace("|", "", $text);
                    $text = str_replace("<br />", " ", $text);
                    $text = str_replace("<br>", " ", $text);
                    $text = str_replace("\n", " ", $text);
                    $text = str_replace("\t", " ", $text);
                    $text = trim($text);
                    $html .= "<div class=\"imSearchPageResult\"><h3><a class=\"imCssLink\" href=\"" . $name . "\">" . strip_tags($title, "<b><strong>") . "</a></h3>" . strip_tags($text, "<b><strong>") . "<div class=\"imSearchLink\"><a class=\"imCssLink\" href=\"" . $name . "\">" . $imSettings['general']['url'] . "/" . $name . "</a></div></div>\n";
                }
            }
            $html = preg_replace_callback('/\\s+/', create_function('$matches', 'return implode(\' \', $matches);'), $html);
            $html .= "<div class=\"imSLabel\">&nbsp;</div>\n";
        }

        return array("content" => $html, "count" => count($found_content));
    }

    function searchBlog($queries)
    {
        
        global $imSettings;
        $html = "";
        $found_content = array();
        $found_count = array();

        if (isset($imSettings['blog']) && is_array($imSettings['blog']['posts'])) {
            foreach ($imSettings['blog']['posts'] as $key => $value) {
                $count = 0;
                $weight = 0;
                $filename = 'blog/index.php?id=' . $key;
                $file_content = $value['body'];
                // Rimuovo le briciole dal contenuto
                while (stristr($file_content, "<div id=\"imBreadcrumb\"") !== false) {
                    $style_start = imstripos($file_content, "<div id=\"imBreadcrumb\"");
                    $style_end = imstripos($file_content, "</div", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }

                // Rimuovo gli stili dal contenuto
                while (stristr($file_content, "<style") !== false) {
                    $style_start = imstripos($file_content, "<style");
                    $style_end = imstripos($file_content, "</style", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }
                // Rimuovo i JS dal contenuto
                while (stristr($file_content, "<script") !== false) {
                    $style_start = imstripos($file_content, "<script");
                    $style_end = imstripos($file_content, "</script", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }
                $file_title = "";

                // Rimuovo il titolo dal risultato
                while (stristr($file_content, "<h2") !== false) {
                    $style_start = imstripos($file_content, "<h2");
                    $style_end = imstripos($file_content, "</h2", $style_start);
                    $style = substr($file_content, $style_start, $style_end - $style_start);
                    $file_content = str_replace($style, "", $file_content);
                }

                // Conto il numero di match nel titolo
                foreach ($queries as $query) {
                    $t_count = @preg_match_all('/' . preg_quote($query, '/') . '/', imstrtolower($value['title']), $matches);
                    if ($t_count !== false) {
                        $weight += ($t_count * 4);
                        $count += $t_count;
                    }
                }

                // Conto il numero di match nei tag
                foreach ($queries as $query) {
                    if (in_array($query, $value['tag'])) {
                        $count++;
                        $weight += 4;
                    }
                }

                $title = "Blog &gt;&gt; " . $value['title'];

                // Cerco nel contenuto
                foreach ($queries as $query) {
                    $file = imstrtolower($file_content);
                    while (($file = stristr($file, $query)) !== false) {
                        $count++;
                        $weight++;
                        $file = substr($file, strlen($query));
                    }
                }

                if ($count > 0) {
                    $found_count[$filename] = $count;
                    $found_weight[$filename] = $weight;
                    $found_content[$filename] = $file_content;
                    $found_breadcrumbs[$filename] = "<div class=\"imBreadcrumb\" style=\"display: block; padding-bottom: 3px;\">" . l10n('blog_published_by') . "<strong> " . $value['author'] . " </strong>" . l10n('blog_in') . " <a href=\"blog/index.php?category=" . $value['category'] . "\" target=\"_blank\" rel=\"nofollow\">" . $value['category'] . "</a> &middot; " . $value['timestamp'] . "</div>";
                    if ($title == "")
                        $found_title[$filename] = $filename;
                    else
                        $found_title[$filename] = $title;
                }
            }
        }

        if (count($found_count)) {
            arsort($found_weight);
            $i = 0;
            foreach ($found_weight as $name => $weight) {
                $count = $found_count[$name];
                $i++;
                if (($i > $this->page*$this->results_per_page) && ($i <= ($this->page+1)*$this->results_per_page)) {
                    $title = strip_tags($found_title[$name]);
                    $file = $found_content[$name];
                    $file = strip_tags($file);
                    $ap = 0;
                    $filelen = strlen($file);
                    $text = "";
                    for ($j=0;$j<($count > 6 ? 6 : $count);$j++) {
                        $minpos = $filelen;
                        foreach ($queries as $query) {
                            if ($ap < $filelen && ($pos = strpos(strtoupper($file), strtoupper($query), $ap)) !== false) {
                                if ($pos < $minpos) {
                                    $minpos = $pos;
                                    $word = $query;
                                }
                            }
                        }
                        $prev = explode(" ", substr($file, $ap, $minpos-$ap));
                        if(count($prev) > ($ap > 0 ? 9 : 8))
                            $prev = ($ap > 0 ? implode(" ", array_slice($prev, 0, 8)) : "") . " ... " . implode(" ", array_slice($prev, -8));
                        else
                            $prev = implode(" ", $prev);
                        $text .= $prev . "<strong>" . substr($file, $minpos, strlen($word)) . "</strong> ";
                        $ap = $minpos + strlen($word);
                    }
                    $next = explode(" ", substr($file, $ap));
                    if(count($next) > 9)
                        $text .= implode(" ", array_slice($next, 0, 8)) . "...";
                    else
                        $text .= implode(" ", $next);
                    $text = str_replace("|", "", $text);
                    $html .= "<div class=\"imSearchBlogResult\"><h3><a class=\"imCssLink\" href=\"" . $name . "\">" . strip_tags($title, "<b><strong>") . "</a></h3>" . strip_tags($found_breadcrumbs[$name], "<b><strong>") . "\n" . strip_tags($text, "<b><strong>") . "<div class=\"imSearchLink\"><a class=\"imCssLink\" href=\"" . $name . "\">" . $imSettings['general']['url'] . "/" . $name . "</a></div></div>\n";
                }
            }
            echo "  <div class=\"imSLabel\">&nbsp;</div>\n";
        }

        $html = preg_replace_callback('/\\s+/', create_function('$matches', 'return implode(\' \', $matches);'), $html);
        return array("content" => $html, "count" => count($found_content));
    }

    // Di questa funzione manca la paginazione!
    function searchProducts($queries)
    {
        
        global $imSettings;
        $html = "";
        $found_products = array();
        $found_count = array();

        foreach ($imSettings['search']['products'] as $id => $product) {
            $count = 0;
            $weight = 0;
            $t_title = strip_tags(imstrtolower($product['name']));
            $t_description = strip_tags(imstrtolower($product['description']));

            // Conto il numero di match nel titolo
            foreach ($queries as $query) {
                $t_count = preg_match_all('/' . preg_quote($query, '/') . '/', $t_title, $matches);
                if ($t_count !== false) {
                    $weight += ($t_count * 4);
                    $count += $t_count;
                }
            }

            // Conto il numero di match nella descrizione
            foreach ($queries as $query) {
                $t_count = preg_match_all('/' . preg_quote($query, '/') . '/', $t_description, $matches);
                if ($t_count !== false) {
                    $weight++;
                    $count += $t_count;
                }
            }

            if ($count > 0) {
                $found_products[$id] = $product;
                $found_weight[$id] = $weight;
                $found_count[$id] = $count;
            }
        }

        if (count($found_count)) {
            arsort($found_weight);
            $i = 0;
            foreach ($found_products as $id => $product) {
                $i++;
                if (($i > $this->page*$this->results_per_page) && ($i <= ($this->page+1)*$this->results_per_page)) {
                    $count = $found_count[$id];
                    $html .= "<div class=\"imSearchProductResult\">";
                    // Top row
                    $html .= "<div class=\"imProductImage\">";
                    $html .= $product['image'];
                    $html .= "</div>";
                    $html .= "<div class=\"imProductDescription\">";
                    $html .= "<div class=\"imProductTitle\">";
                    $html .= "<h3>" . $product['name'] . "</h3>";
                    $html .= "<span>" . $product['price'] . "<img src=\"cart/images/cart-add.png\" onclick=\"x5engine.cart.ui.addToCart('" . $id . "', 1);\" style=\"cursor: pointer;\" /></span>";
                    $html .= "</div>";
                    $html .= "<p>" . strip_tags($product['description']) . "</p>";
                    $html .= "</div>";
                    // Close the container
                    $html .= "</div>";
                }
            }
        }

        return array("content" => $html, "count" => count($found_products));
    }

    // Di questa funzione manca la paginazione!
    function searchImages($queries)
    {
        
        global $imSettings;
        $id = 0;
        $html = "";
        $found_images = array();
        $found_count = array();

        foreach ($imSettings['search']['images'] as $image) {
            $count = 0;
            $weight = 0;
            $t_title = strip_tags(imstrtolower($image['title']));
            $t_description = strip_tags(imstrtolower($image['description']));

            // Conto il numero di match nel titolo
            foreach ($queries as $query) {
                $t_count = preg_match_all('/' . preg_quote($query, '/') . '/', $t_title, $matches);
                if ($t_count !== false) {
                    $weight += ($t_count * 4);
                    $count += $t_count;
                }
            }

            // Conto il numero di match nella location
            foreach ($queries as $query) {
                $t_count = preg_match_all('/' . preg_quote($query, '/') . '/', imstrtolower($image['location']), $matches);
                if ($t_count !== false) {
                    $weight += ($t_count * 2);
                    $count += $t_count;
                }
            }

            // Conto il numero di match nella descrizione
            foreach ($queries as $query) {
                $t_count = preg_match_all('/' . preg_quote($query, '/') . '/', $t_description, $matches);
                if ($t_count !== false) {
                    $weight++;
                    $count += $t_count;
                }
            }

            if ($count > 0) {
                $found_images[$id] = $image;
                $found_weight[$id] = $weight;
                $found_count[$id] = $count;
            }

            $id++;
        }

        if (count($found_count)) {
            arsort($found_weight);
            $i = 0;
            foreach ($found_images as $id => $image) {
                $i++;
                if (($i > $this->page*$this->results_per_page) && ($i <= ($this->page+1)*$this->results_per_page)) {
                    $count = $found_count[$id];
                    $html .= "<div class=\"imSearchImageResult\">";
                    $html .= "<div class=\"imSearchImageResultContent\"><a href=\"" . $image['page'] . "\"><img src=\"" . $image['src'] . "\" /></a></div>";
                    $html .= "<div class=\"imSearchImageResultContent\">";
                    $html .= "<h3>" . $image['title'];
                    if ($image['location'] != "")
                        $html .= "&nbsp;(" . $image['location'] . ")";
                    $html .= "</h3>";
                    $html .= strip_tags($image['description']);
                    $html .= "</div>";
                    $html .= "</div>";
                }
            }
        }

        return array("content" => $html, "count" => count($found_images));
    }

    // Di questa funzione manca la paginazione!
    function searchVideos($queries)
    {
        
        global $imSettings;
        $id = 0;
        $found_count = array();
        $found_videos = array();
        $html = "";
        $month = 7776000;

        foreach ($imSettings['search']['videos'] as $video) {
            $count = 0;
            $weight = 0;
            $t_title = strip_tags(imstrtolower($video['title']));
            $t_description = strip_tags(imstrtolower($video['description']));

            // Conto il numero di match nei tag
            foreach ($queries as $query) {
                $t_count = preg_match_all('/\\s*' . preg_quote($query, '/') . '\\s*/', imstrtolower($video['tags']), $matches);
                if ($t_count !== false) {
                    $weight += ($t_count * 10);
                    $count += $t_count;
                }
            }

            // I video più recenti hanno maggiore peso in proporzione
            $time = strtotime($video['date']);
            $ago = strtotime("-3 months");
            if ($time - $ago > 0)
                $weight += 5 * max(0, ($time - $ago)/$month);

            // Conto il numero di match nel titolo
            foreach ($queries as $query) {
                $t_count = preg_match_all('/' . preg_quote($query, '/') . '/', $t_title, $matches);
                if ($t_count !== false) {
                    $weight += ($t_count * 4);
                    $count += $t_count;
                }
            }

            // Conto il numero di match nella categoria
            foreach ($queries as $query) {
                $t_count = preg_match_all('/' . preg_quote($query, '/') . '/', imstrtolower($video['category']), $matches);
                if ($t_count !== false) {
                    $weight += ($t_count * 2);
                    $count += $t_count;
                }
            }

            // Conto il numero di match nella descrizione
            foreach ($queries as $query) {
                $t_count = preg_match_all('/' . preg_quote($query) . '/', $t_description, $matches);
                if ($t_count !== false) {
                    $weight++;
                    $count += $t_count;
                }
            }

            if ($count > 0) {
                $found_videos[$id] = $video;
                $found_weight[$id] = $weight;
                $found_count[$id] = $count;
            }

            $id++;
        }

        if ($found_count) {
            arsort($found_weight);
            foreach ($found_videos as $id => $video) {
                $i++;
                if (($i > $this->page*$this->results_per_page) && ($i <= ($this->page+1)*$this->results_per_page)) {
                    $count = $found_count[$id];
                    $html .= "<div class=\"imSearchVideoResult\">";
                    $html .= "<div class=\"imSearchVideoResultContent\"><a href=\"" . $video['page'] . "\"><img src=\"" . $video['src'] . "\" /></a></div>";
                    $html .= "<div class=\"imSearchVideoResultContent\">";
                    $html .= "<h3>" . $video['title'];
                    if (!$video['familyfriendly'])
                        $html .= "&nbsp;<span style=\"color: red; text-decoration: none;\">[18+]</span>";
                    $html .= "</h3>";
                    $html .= strip_tags($video['description']);
                    if ($video['duration'] > 0) {
                        if (function_exists('date_default_timezone_set'))
                            date_default_timezone_set('UTC');
                        $html .= "<span class=\"imSearchVideoDuration\">" . l10n('search_duration') . ": " . date("H:i:s", $video['duration']) . "</span>";
                    }
                    $html .= "</div>";
                    $html .= "</div>";
                }
            }
        }

        return array("content" => $html, "count" => count($found_videos));
    }

    /**
     * Start the site search
     * 
     * @param array  $keys The search keys as string (string)
     * @param string $page Page to show (integer)
     * @param string $type The content type to show
     *
     * @return void
     */
    function search($keys, $page, $type)
    {
        global $imSettings;

        $html = "";
        $content = "";
        $emptyResultsHtml = "<div style=\"margin-top: 15px; text-align: center; font-weight: bold;\">" . l10n('search_empty') . "</div>\n";

        $html .= "<div class=\"imPageSearchField\"><form method=\"get\" action=\"imsearch.php\">";
        $html .= "<input style=\"width: 200px; font: 8pt Tahoma; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255); padding: 3px; border: 1px solid rgb(0, 0, 0); vertical-align: middle;\" class=\"search_field\" value=\"" . htmlspecialchars($keys, ENT_COMPAT, 'UTF-8') . "\" type=\"text\" name=\"search\" />";
        $html .= "<input style=\"height: 21px; font: 8pt Tahoma; color: rgb(0, 0, 0); background-color: rgb(211, 211, 211); margin-left: 6px; padding: 3px 3px; border: 1px solid rgb(0, 0, 0); vertical-align: middle; cursor: pointer;\" type=\"submit\" value=\"" . l10n('search_search') . "\">";
        $html .= "</form></div>\n";

        // Exit if no search query was given
        if (trim($keys) == "" || $keys == null) {
            $html .= $emptyResultsHtml;
            return $html;
        }

        $search = trim(imstrtolower($keys));
        $this->page = $page;

        $queries = explode(" ", $search);

        // Search everywhere to populate the results numbers shown in the sidebar menu
        // Pages
        $pages = $this->searchPages($queries);
        // Fallback on the selection if there are no pages
        if ($pages['count'] == 0 && $type == "pages")
            $type = "blog";

        // Blog
        if (isset($imSettings['blog']) && is_array($imSettings['blog']['posts']) && count($imSettings['blog']['posts']) > 0)
            $blog = $this->searchBlog($queries);
        else
            $blog = array("count" => 0);
        // Fallback on the selection if there is no blog
        if ($blog['count'] == 0 && $type == "blog")
            $type = "products";

        // Products
        if (is_array($imSettings['search']['products']) && count($imSettings['search']['products']) > 0)
            $products = $this->searchProducts($queries);
        else
            $products = array("count" => 0);
        // Fallback on the selection if there are no products
        if ($products['count'] == 0 && $type == "products")
            $type = "images";

        // Images
        if (is_array($imSettings['search']['images']) && count($imSettings['search']['images']) > 0)
            $images = $this->searchImages($queries);
        else
            $images = array("count" => 0);
        // Fallback on the selection if there are no images
        if ($images['count'] == 0 && $type == "images")
            $type = "videos";

        // Videos
        if (is_array($imSettings['search']['videos']) && count($imSettings['search']['videos']) > 0)
            $videos = $this->searchVideos($queries);
        else
            $videos = array("count" => 0);
        // Fallback on the selection if there are no videos
        if ($videos['count'] == 0 && $type == "videos")
            $type = "pages";            

        // Show only the requested content type
        switch ($type) {
            case "pages":
                if ($pages['count'] > 0)
                    $content .= "<div>" . $pages['content'] . "</div>\n";
                $results_count = $pages['count'];
                break;
            case "blog":
                if ($blog['count'] > 0)
                    $content .= "<div>" . $blog['content'] . "</div>\n";
                $results_count = $blog['count'];
                break;
            case "products":
                if ($products['count'] > 0)
                    $content .= "<div>" . $products['content'] . "</div>\n";
                $results_count = $products['count'];
                break;
            case "images":
                if ($images['count'] > 0)
                    $content .= "<div>" . $images['content'] . "</div>\n";
                $results_count = $images['count'];
                break;
            case "videos":
                if ($videos['count'] > 0)
                    $content .= "<div>" . $videos['content'] . "</div>\n";
                $results_count = $videos['count'];
                break;
        }

        // Exit if there are no results
        if (!$results_count) {
            $html .= $emptyResultsHtml;
            return $html;
        }

        $sidebar = "<ul>\n";
        if ($pages['count'] > 0)
            $sidebar .= "\t<li><span class=\"imScMnTxt\"><a href=\"imsearch.php?search=" . urlencode($keys) . "&type=pages\">" . l10n('search_pages') . " (" . $pages['count'] . ")</a></span></li>\n";
        if ($blog['count'] > 0)
            $sidebar .= "\t<li><span class=\"imScMnTxt\"><a href=\"imsearch.php?search=" . urlencode($keys) . "&type=blog\">" . l10n('search_blog') . " (" . $blog['count'] . ")</a></span></li>\n";
        if ($products['count'] > 0)
            $sidebar .= "\t<li><span class=\"imScMnTxt\"><a href=\"imsearch.php?search=" . urlencode($keys) . "&type=products\">" . l10n('search_products') . " (" . $products['count'] . ")</a></span></li>\n";
        if ($images['count'] > 0)
            $sidebar .= "\t<li><span class=\"imScMnTxt\"><a href=\"imsearch.php?search=" . urlencode($keys) . "&type=images\">" . l10n('search_images') . " (" . $images['count'] . ")</a></span></li>\n";
        if ($videos['count'] > 0)
            $sidebar .= "\t<li><span class=\"imScMnTxt\"><a href=\"imsearch.php?search=" . urlencode($keys) . "&type=videos\">" . l10n('search_videos') . " (" . $videos['count'] . ")</a></span></li>\n";
        $sidebar .= "</ul>\n";

        $html .= "<div id=\"imSearchResults\">\n";
        if ($imSettings['search']['general']['menu_position'] == "left") {
            $html .= "\t<div id=\"imSearchSideBar\" style=\"float: left;\">" . $sidebar . "</div>\n";
            $html .= "\t<div id=\"imSearchContent\" style=\"float: right;\">" . $content . "</div>\n";
        } else {
            $html .= "\t<div id=\"imSearchContent\" style=\"float: left;\">" . $content . "</div>\n";
            $html .= "\t<div id=\"imSearchSideBar\" style=\"float: right;\">" . $sidebar . "</div>\n";
        }
        $html .= "</div>\n";

        // Pagination
        if ($results_count > $this->results_per_page) {
            $html .= "<div style=\"text-align: center; clear: both;\">";
            // Back
            if ($page > 0) {
                $html .= "<a href=\"imsearch.php?search=" . implode("+", $queries) . "&amp;page=" . ($page - 1) . "&type=" . $type . "\">&lt;&lt;</a>&nbsp;";
            }

            // Central pages
            $start = max($page - 5, 0);
            $end = min($page + 10 - $start, ceil($results_count/$this->results_per_page));

            for ($i = $start; $i < $end; $i++) {
                if ($i != $this->page)
                    $html .= "<a href=\"imsearch.php?search=" . implode("+", $queries) . "&amp;page=" . $i . "&type=" . $type . "\">" . ($i + 1) . "</a>&nbsp;";
                else
                    $html .= ($i + 1) . "&nbsp;";
            }

            // Next
            if ($results_count > ($page + 1) * $this->results_per_page) {
                $html .= "<a href=\"imsearch.php?search=" . implode("+", $queries) . "&amp;page=" . ($page + 1) . "&type=" . $type . "\">&gt;&gt;</a>";
            }
            $html .= "</div>";
        }

        return $html;
    }
}


/**
 * Contains the methods used to style and send emails
 * @access public
 */
class ImSendEmail
{

    var $header;
    var $footer;
    var $bodyBackground;
    var $bodyBackgroundEven;
    var $bodyBackgroundOdd;
    var $bodyBackgroundBorder;
    var $bodySeparatorBorderColor;
    var $emailBackground;
    var $emailContentStyle;
    var $emailContentFontFamily;
    var $emailType = "html";
    var $exposeWsx5 = true;

    function setExpose($expose)
    {
        $this->exposeWsx5 = $expose;
    }

    function setHTMLHeader($header)
    {
        $this->header = $header;
    }

    function setHTMLFooter($footer)
    {
        $this->footer = $footer;
    }

    function setBodyBackground($val)
    {
        $this->bodyBackground = $val;
    }

    function setBodyBackgroundEven($val)
    {
        $this->bodyBackgroundEven = $val;
    }

    function setBodyBackgroundOdd($val)
    {
        $this->bodyBackgroundOdd = $val;
    }

    function setBodyBackgroundBorder($val)
    {
        $this->bodyBackgroundBorder = $val;
    }

    function setEmailBackground($val)
    {
        $this->emailBackground = $val;
    }

    function setEmailContentStyle($val)
    {
        $this->emailContentStyle = $val;
    }

    function setEmailContentFontFamily($val)
    {
        $this->emailContentFontFamily = $val;
    }

    function setBodySeparatorBorderColor($val)
    {
        $this->bodySeparatorBorderColor = $val;
    }

    function setEmailType($type) {
        $this->emailType = $type;
    }

    /**
     * Apply the CSS style to the HTML code
     * @param  string $html The HTML code
     * @return string       The styled HTML code
     */
    function styleHTML($html)
    {
        $html = str_replace("[email:contentStyle]", $this->emailContentStyle, $html);
        $html = str_replace("[email:contentFontFamily]", $this->emailContentFontFamily, $html);
        $html = str_replace("[email:bodyBackground]", $this->bodyBackground, $html);
        $html = str_replace("[email:bodyBackgroundBorder]", $this->bodyBackgroundBorder, $html);
        $html = str_replace("[email:bodyBackgroundOdd]", $this->bodyBackgroundOdd, $html);
        $html = str_replace("[email:bodyBackgroundEven]", $this->bodyBackgroundEven, $html);
        $html = str_replace("[email:bodySeparatorBorderColor]", $this->bodySeparatorBorderColor, $html);
        $html = str_replace("[email:emailBackground]", $this->emailBackground, $html);
        return $html;
    }

    /**
     * Send an email
     * 
     * @param string $from        Self explanatory
     * @param string $to          Self explanatory
     * @param string $subject     Self explanatory
     * @param string $text        Self explanatory
     * @param string $html        Self explanatory
     * @param array  $attachments Self explanatory
     * 
     * @return boolean
     */
    function send($from = "", $to = "", $subject = "", $text = "", $html = "", $attachments = array())
    {
        $email = new imEMail($from, $to, $subject, "utf-8");
        $email->setExpose($this->exposeWsx5);
        $email->setText($text);
        $email->setHTML($this->header . $this->styleHTML($html) . $this->footer);
        $email->setStandardType($this->emailType);
        foreach ($attachments as $a) {
            if (isset($a['name']) && isset($a['content']) && isset($a['mime'])) {
                $email->attachFile($a['name'], $a['content'], $a['mime']);
            }
        }
        return $email->send();
    }

    /**
     * Restore some special chars escaped previously in WSX5
     * 
     * @param string $str The string to be restored
     *
     * @return string
     */
    function restoreSpecialChars($str)
    {
        $str = str_replace("{1}", "'", $str);
        $str = str_replace("{2}", "\"", $str);
        $str = str_replace("{3}", "\\", $str);
        $str = str_replace("{4}", "<", $str);
        $str = str_replace("{5}", ">", $str);
        return $str;
    }

    /**
     * Decode the Unicode escaped chars like %u1239
     * 
     * @param string $str The string to be decoded
     *
     * @return string
     */
    function decodeUnicodeString($str)
    {
        $res = '';

        $i = 0;
        $max = strlen($str) - 6;
        while ($i <= $max) {
            $character = $str[$i];
            if ($character == '%' && $str[$i + 1] == 'u') {
                $value = hexdec(substr($str, $i + 2, 4));
                $i += 6;

                if ($value < 0x0080) // 1 byte: 0xxxxxxx
                    $character = chr($value);
                else if ($value < 0x0800) // 2 bytes: 110xxxxx 10xxxxxx
                    $character = chr((($value & 0x07c0) >> 6) | 0xc0) . chr(($value & 0x3f) | 0x80);
                else // 3 bytes: 1110xxxx 10xxxxxx 10xxxxxx
                $character = chr((($value & 0xf000) >> 12) | 0xe0) . chr((($value & 0x0fc0) >> 6) | 0x80) . chr(($value & 0x3f) | 0x80);
            } else
                $i++;

            $res .= $character;
        }
        return $res . substr($str, $i);
    }
}

/**
 * Server Test Class
 * @access public
 */
class imTest {

    /*
     * Session check
     */
    function session_test()
    {
        
        if (!isset($_SESSION))
            return false;
        $_SESSION['imAdmin_test'] = "test_message";
        return ($_SESSION['imAdmin_test'] == "test_message");
    }

    /*
     * Writable files check
     */
    function writable_folder_test($dir)
    {
        if (!file_exists($dir) && $dir != "" && $dir != "./.")
            @mkdir($dir, 0777, true);

        $fp = @fopen(pathCombine(array($dir, "imAdmin_test_file")), "w");
        if (!$fp)
            return false;
        if (@fwrite($fp, "test") === false)
            return false;
        @fclose($fp);
        if (!@file_exists(pathCombine(array($dir, "imAdmin_test_file"))))
            return false;
        @unlink(pathCombine(array($dir, "imAdmin_test_file")));
        return true;
    }

    /*
     * PHP Version check
     */
    function php_version_test()
    {   
        if (!function_exists("version_compare") || version_compare(PHP_VERSION, '4.0.0') < 0)
            return false;
        return true;
    }

    /*
     * MySQL Connection check
     */
    function mysql_test($host, $user, $pwd, $name)
    {
        $db = new ImDb($host, $user, $pwd, $name);
        if (!$db->testConnection())
            return false;
        $db->closeConnection();
        return true;
    }

    /*
     * Do the test
     */
    function doTest($expected, $value, $title, $message)
    {
        if ($expected == $value)
            echo "<div class=\"imTest pass\">" . $title . "<span>PASS</span></div>";
        else
            echo "<div class=\"imTest fail\">" . $title . "<span>FAIL</span><p>" . $message . "</p></div>";
    }
}



/**
 * This file stores the class used to show a topic
 *
 * @category X5engine
 * @package  X5engine
 * @license  Copyright by Incomedia http://incomedia.eu
 * @link     http://websitex5.com
 */
class ImTopic
{

    var $id;
    var $comments    = null;
    var $table       = "";
    var $folder      = "";
    var $host        = "";
    var $user        = "";
    var $pwd         = "";
    var $database    = "";
    var $ratingImage = "";
    var $storageType = "xml";
    var $basepath    = "";
    var $posturl     = "";
    var $title       = "";

    /**
     * Constructor for PHP5
     * 
     * @param string $id       The topic id
     * @param string $basepath The base path
     * @param string $postUrl  The URL to post to
     *
     * @return void
     */
    function __construct($id, $basepath = "", $postUrl = "")
    {
        $this->setUp($id, $basepath, $postUrl);
    }

    /**
     * Constructor for PHP4
     * 
     * @param string $id       The topic id
     * @param string $basepath The base path
     * @param string $postUrl  The URL to post to
     *
     * @return void
     */
    function ImTopic($id, $basepath = "", $postUrl = "")
    {
        $this->setUp($id, $basepath, $postUrl);
    }

    /**
     * Do the constructor actions
     * 
     * @param string $id       The topic is
     * @param string $basepath The basepath of the page which loads the topic
     * @param string $postUrl  The URL to post to
     *
     * @return void
     */
    function setUp($id, $basepath = "", $postUrl = "")
    {
        $this->id = $id;
        if (strlen($postUrl)) {
            $this->posturl = trim($postUrl, "?&");
            $this->posturl .=(strpos($this->posturl, "?") === false ? "?" : "&");
        } else {
            $this->posturl = basename($_SERVER['PHP_SELF']) . "?";
        }
        $this->basepath = $this->prepFolder($basepath);
        // Create the comments array
        $this->comments = new ImComment();
    }

    /**
     * Set the path to wich the data is posted to
     * 
     * @param string $posturl The url to post to
     *
     * @return void
     */
    function setPostUrl($posturl)
    {
        $this->posturl = $posturl . (strpos($posturl, "?") === 0 ? "?" : "&");
    }

    /**
     * Set the title of this topic
     * 
     * @param string $title
     *
     * @return  void
     */
    function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Return the encrypted filename of a string
     * @param  string $str
     * @return string
     */
    function encFileName($str)
    {
        return substr(md5($str), 0, 8) . substr($str, -4);
    }

    /**
     * Load the data from the xml file
     * 
     * @param string $folder The file's folder
     * 
     * @return void
     */
    function loadXML($folder = "")
    {
        if ($this->comments == null)
            return;

        $this->folder = $this->prepFolder($folder);
        $encName = $this->encFileName($this->id);
        // Check if the encrypted filename exists
        if (file_exists($this->basepath . $this->folder . $encName))
            $this->comments->loadFromXML($this->basepath . $this->folder . $encName);
        // If the encrypted filename doesn't exist, try the normal filename
        else
            $this->comments->loadFromXML($this->basepath . $this->folder . $this->id);
        $this->storageType = "xml";
    }

    /**
     * Save to xml
     * 
     * @param string $folder The folder where is saved the file
     * 
     * @return boolean
     */
    function saveXML($folder = "")
    {
        if ($this->comments == null)
            return;

        $encName = $this->encFileName($this->id);
        $folder = $folder != "" ? $this->prepFolder($folder) : $this->folder;
        if ($this->comments->saveToXML($this->basepath . $folder . $encName)) {
            // If the comments can be saved, check if the non-encrypted file exists. If so, delete it.
            if (file_exists($this->basepath . $this->folder . $this->id))
                unlink($this->basepath . $this->folder . $this->id);
            return true;
        }
        return false;
    }

    /**
     * Setup the folder
     * 
     * @param string $folder The folder path to prepare
     * 
     * @return string
     */
    function prepFolder($folder)
    {
        if (strlen(trim($folder)) == 0)
            return "./";

        if (substr($folder, 0, -1) != "/")
            $folder .= "/";

        return $folder;
    }


    /**
     * Load the data from the database
     * 
     * @param string $host  The dbname
     * @param string $user  The db user name
     * @param string $pwd   The db user password
     * @param string $db    The db name
     * @param string $table The db table
     * 
     * @return void
     */
    function loadDb($host, $user, $pwd, $db, $table)
    {
        if ($this->comments == null)
            return;

        $this->host  = $host;
        $this->user  = $user;
        $this->pwd   = $pwd;
        $this->db    = $db;
        $this->table = $table;
        $this->storageType = "database";

        $this->comments->loadFromDb($this->host, $this->user, $this->pwd, $this->db, $this->table, $this->id);
    }

    /**
     * Save the comments to a database
     * 
     * @param string $host  The dbname
     * @param string $user  The db user name
     * @param string $pwd   The db user password
     * @param string $db    The db name
     * @param string $table The db table
     * 
     * @return boolean
     */
    function saveDb($host = "", $user = "", $pwd = "", $db = "", $table = "")
    {
        if ($this->comments == null)
            return false;

        $host  = $host != "" ? $host : $this->host;
        $user  = $user != "" ? $user : $this->user;
        $pwd   = $pwd != "" ? $pwd : $this->pwd;
        $db    = $db != "" ? $db : $this->db;
        $table = $table != "" ? $table : $this->table;
        return $this->comments->saveToDb($host, $user, $pwd, $db, $table, $this->id);
    }


    /**
     * Checks the $_POST array for new messages
     * 
     * @param boolean $moderate    TRUE to show only approved comments
     * @param string  $email       The email to notify the new comment
     * @param string  $type        The topic type (guestbook|blog)
     * @param string  $moderateurl The url where the user can moderate the comments
     * 
     * @return booelan
     */
    function checkNewMessages($moderate = true, $email = "", $type = "guestbook", $moderateurl = "")
    {
        global $ImMailer;
        global $imSettings;

        /*
        |-------------------------------------------
        |    Check for new messages
        |-------------------------------------------
         */

        if (!isset($_POST['x5topicid']) || $_POST['x5topicid'] != $this->id)
            return false;
        if (!checkJsAndSpam())
            return false;

        $comment = array(
            "email"     => $_POST['email'],
            "name"      => $_POST['name'],
            "url"       => $_POST['url'],
            "body"      => $_POST['body'],
            "ip"        => $_SERVER['REMOTE_ADDR'],
            "timestamp" => date("Y-m-d H:i:s"),
            "abuse"     => "0",
            "approved"  => $moderate ? "0" : "1"
        );
        if (isset($_POST['rating']))
            $comment['rating'] = $_POST['rating'];
        $this->comments->add($comment);
        $saved = ($this->storageType == "xml" ? $this->saveXML() : $this->saveDb());
        if (!$saved) {
            echo "<script type=\"text/javascript\">window.top.location.href='" . $this->posturl . $this->id . "error';</script>";
            return false;
        }
        // Send the notification email
        if ($email != "") {
            if ($type == "guestbook")
                $html = str_replace(array("Blog", "blog"), array("Guestbook", "guestbook"), l10n('blog_new_comment_text')) . " \"" . $this->title . "\":<br /><br />\n\n";
            else
                $html = l10n('blog_new_comment_text') . ":<br /><br />\n\n";
            $html .= "<b>" . l10n('blog_name') . "</b> " . stripslashes($_POST['name']) . "<br />\n";
            $html .= "<b>" . l10n('blog_email') . "</b> " . $_POST['email'] . "<br />\n";
            $html .= "<b>" . l10n('blog_website') . "</b> " . $_POST['url'] . "<br />\n";
            if (isset($_POST['rating']))
                $html .= "<b>" . l10n('blog_rating', "Vote:") . "</b> " . $_POST['rating'] . "/5<br />\n";
            $html .= "<b>" . l10n('blog_message') . "</b> " . stripslashes($_POST['body']) . "<br /><br />\n\n";
            // Set the proper link
            if ($moderateurl != "") {
                $html .= ($moderate ? l10n('blog_unapprove_link') : l10n('blog_approve_link')) . ":<br />\n";
                $html .= "<a href=\"" . $moderateurl . "\">" . $moderateurl . "</a>";
            }
            if ($type == "guestbook")
                $subject = str_replace(array("Blog", "blog"), array("Guestbook", "guestbook"), l10n('blog_new_comment_object'));
            else
                $subject = l10n('blog_new_comment_object');
            $ImMailer->send(strlen($comment['email']) ? $comment['email'] : $email, $email, $subject, strip_tags($html), $html);
        }

        // Redirect
        echo "<script type=\"text/javascript\">window.top.location.href='" . $this->posturl . ($moderate ? $this->id . "success" : "") . "';</script>";
        return true;
    }

    /**
     * Check for new abuses
     * 
     * @return void
     */
    function checkNewAbuses()
    {
        if (isset($_GET['x5topicid']) && $_GET['x5topicid'] == $this->id) {
            if (isset($_GET['abuse'])) {
                $n = (int)$_GET['abuse'];
                $c = $this->comments->get($n);
                $c['abuse'] = "1";
                $this->comments->edit($n, $c);
                $this->storageType == "xml" ? $this->saveXML() : $this->saveDb();
                echo "<script type=\"text/javascript\">window.top.location.href='" . $this->posturl . "';</script>";
            }
        }
    }

    /**
     * Show the comments form
     * 
     * @param boolean $rating      true to show the rating
     * @param boolean $captcha     true to enable captcha
     * @param boolean $moderate    true to enable the moderation
     * @param string  $email       the email address to notificate
     * @param string  $type        guestbook or blog
     * @param string  $moderateurl The url at wich is possible to moderate the new comments
     * 
     * @return void
     */
    function showForm($rating = true, $captcha = true, $moderate = true, $email = "", $type = "guestbook", $moderateurl = "")
    {
        global $imSettings;
        $id = $this->id . "-topic-form";

        $this->checkNewMessages($moderate, $email, $type, $moderateurl);
        $this->checkNewAbuses();

        /*
        |-------------------------------------------
        |    Show the form
        |-------------------------------------------
         */
        
        if (isset($_GET[$this->id . 'success'])) {
            echo "<div class=\"alert alert-green\">" . l10n('blog_send_confirmation') . "</div>";
        } else if (isset($_GET[$this->id . 'error'])) {
            echo "<div class=\"alert alert-red\">" . l10n('blog_send_error') . "</div>";
        }

        echo "<div class=\"topic-form\">
              <form id=\"" . $id ."\" action=\"" . $this->posturl . "\" method=\"post\" onsubmit=\"return x5engine.imForm.validate(this, {type: 'tip', showAll: true})\">
                <input type=\"hidden\" name=\"post_id\" value=\"" . $this->id . "\"/>
                <div class=\"topic-form-row\">
                    <label for=\"" . $id . "-name\" style=\"float: left; width: 100px;\">" . l10n('blog_name') . "*</label> <input type=\"text\" id=\"" . $id . "-name\" name=\"name\" class=\"imfield mandatory\" />
                </div>
                <div class=\"topic-form-row\">
                    <label for=\"" . $id . "-email\" style=\"float: left; width: 100px;\">" . l10n('blog_email') . "*</label> <input type=\"text\" id=\"" . $id . "-email\" name=\"email\" class=\"imfield mandatory valEmail\"/>
                </div>
                <div class=\"topic-form-row\">
                    <label for=\"" . $id . "-url\" style=\"float: left; width: 100px;\">" . l10n('blog_website') . "</label> <input type=\"text\" id=\"" . $id . "-url\" name=\"url\" />
                </div>";
        if ($rating) {
            echo "<div class=\"topic-form-row\">
                <label style=\"float: left; width: 100px;vertical-align: middle;\">" . l10n('blog_rating', "Vote") . "</label>
                <span class=\"topic-star-container-big variable-star-rating\">
                    <span class=\"topic-star-fixer-big\" style=\"width: 0;\"></span>
                </span>
                </div>";
        }
                echo "<div class=\"topic-form-row\">
                    <br /><label for=\"" . $id . "-body\" style=\"clear: both; width: 100px;\">" . l10n('blog_message') . "*</label><textarea id=\"" . $id . "-body\" name=\"body\" class=\"imfield mandatory\" style=\"width: 95%; height: 100px;\"></textarea>
                </div>";
        if ($captcha) {
            echo "<div class=\"topic-form-row\" style=\"text-align: center\">
                    <label for=\"" . $id . "_imCpt\" style=\"float: left;\">" . l10n('form_captcha_title') . "</label>&nbsp;<input type=\"text\" id=\"" . $id . "_imCpt\" name=\"imCpt\" maxlength=\"5\" class=\"imfield imCpt[5" . ($type == "blog" ? ", ../" : "") . "]\" size=\"5\" style=\"width: 120px; margin: 0 auto;\" />
                </div>";
        }
        echo "<input type=\"hidden\" value=\"" . $this->id . "\" name=\"x5topicid\">";
        echo "<input type=\"text\" value=\"\" name=\"prt\" class=\"prt_field\">";
        echo "<div class=\"topic-form-row\" style=\"text-align: center\">
                    <input type=\"submit\" value=\"" . l10n('blog_send') . "\" />
                    <input type=\"reset\" value=\"" . l10n('form_reset') . "\" />
                </div>
                </form>
                <script type=\"text/javascript\">x5engine.boot.push( function () { x5engine.imForm.initForm('#" . $id . "', false, { showAll: true }); });</script>
            </div>\n";
    }

    /**
     * Show the topic summary
     * 
     * @param boolean $rating      TRUE to show the ratings
     * @param boolean $admin       TRUE to show approved and unapproved comments
     * @param boolean $hideifempty true to hide the summary if there are no comments
     * 
     * @return void
     */
    function showSummary($rating = true, $admin = false, $hideifempty = true)
    {
        $c = $this->comments->getAll();
        $comments = array();
        $votes = 0;
        $votescount = 0;
        foreach ($c as $comment) {
            if ($comment['approved'] == "1" || $admin) {
                if (isset($comment['body'])) {
                    $comments[] = $comment;
                }
                if (isset($comment['rating'])) {
                    $votes += $comment['rating'];
                    $votescount++;
                }
            }
        }
        $count = count($comments);
        $vote = $votescount > 0 ? $votes/$votescount : 0;

        if ($count == 0 && $hideifempty)
            return;

        echo "<div class=\"topic-summary\">\n";
        echo "<div>" . ($count > 0 ? $count . " " . ($count > 1 ? l10n('blog_comments') : l10n('blog_comment')) : l10n('blog_no_comment')) . "</div>";
        if ($rating) {
            echo "<div style=\"margin-bottom: 5px;\">" . l10n("blog_average_rating", "Average Vote") . ": " . number_format($vote, 1) . "/5</div>";
            echo "<span class=\"topic-star-container-big\" title=\"" . number_format($vote, 1) . "/5\">
                    <span class=\"topic-star-fixer-big\" style=\"width: " . round($vote/5 * 100) . "%;\"></span>
            </span>\n";
        }
        echo "</div>\n";
    }

    /**
     * Show the comments list
     * 
     * @param boolean $rating      true to show the ratings
     * @param string  $order       desc or asc
     * @param boolean $showabuse   true to show the "Abuse" button
     * @param boolean $hideifempty true to hide the summary if there are no comments
     * 
     * @return void
     */
    function showComments($rating = true, $order = "desc", $showabuse = true, $hideifempty = false)
    {
        
        global $imSettings;

        $c = $this->comments->getAll("timestamp", $order);

        if (count($c) == 0 && $hideifempty)
            return;

        echo "<div class=\"topic-comments\">\n";
        if (count($c) > 0) {
            // Check aproved comments count
            $ca = array();
            foreach($c as $comment)
                if($comment['approved'] == "1")
                    $ca[] = $comment;

            // Show the comments
            $i = 0;
            foreach ($ca as $comment) {
                if (isset($comment['body']) && $comment['approved'] == "1") {
                    echo "<div class=\"topic-comment\">\n";
                    echo "<div class=\"topic-comments-user\">" . (stristr($comment['url'], "http") ? "<a href=\"" . $comment['url'] . "\" target=\"_blank\" " . (strpos($comment['url'], $imSettings['general']['url']) === false ? 'rel="nofollow"' : '') . ">" . $comment['name'] . "</a>" : $comment['name']);
                    if ($rating && isset($comment['rating']) && $comment['rating'] > 0) {
                        echo "<span class=\"topic-star-container-small\" title=\"" . $comment['rating'] . "/5\" style=\"margin-left: 5px; vertical-align: middle;\">
                                    <span class=\"topic-star-fixer-small\" style=\"width: " . round($comment['rating']/5 * 100) . "%;\"></span>
                            </span>\n";
                    }
                    echo "</div>\n";
                    echo "<div class=\"topic-comments-date imBreadcrumb\">" . $comment['timestamp'] . "</div>\n";
                    echo "<div class=\"topic-comments-body\">" . $comment['body'] . "</div>\n";
                    if ($showabuse) {
                        echo "<div class=\"topic-comments-abuse\"><a href=\"" . $this->posturl . "x5topicid=" . $this->id . "&amp;abuse=" . $i++ . "\">" . l10n('blog_abuse') . "<img src=\"" . $this->basepath . "res/exclamation.png\" alt=\"" . l10n('blog_abuse') . "\" title=\"" . l10n('blog_abuse') . "\" /></a></div>\n";
                    }
                    echo "</div>\n";
                }
                $i++;
            }
        } else {
            echo "<div>" . l10n('blog_no_comment') . "</div>\n";
        }
        echo "</div>\n";        
    }

    /**
     * Show the comments list in a administration section
     * 
     * @param boolean $rating true to show the ratings
     * @param string  $order  desc or asc
     * 
     * @return void
     */
    function showAdminComments($rating = true, $order = "desc")
    {
        
        global $imSettings;
        $this->comments->sort("ts", $order);

        if (isset($_GET['disable'])) {
            $n = (int)$_GET['disable'];
            $c = $this->comments->get($n);
            if (count($c) != 0) {
                $c['approved'] = "0";
                $this->comments->edit($n, $c);
                $this->storageType == "xml" ? $this->saveXML() : $this->saveDb();
            }
        }

        if (isset($_GET['enable'])) {
            $n = (int)$_GET['enable'];
            $c = $this->comments->get($n);
            if (count($c) != 0) {
                $c['approved'] = "1";
                $this->comments->edit($n, $c);
                $this->storageType == "xml" ? $this->saveXML() : $this->saveDb();
            }
        }

        if (isset($_GET['delete'])) {
            $this->comments->delete((int)$_GET['delete']);
            $this->storageType == "xml" ? $this->saveXML() : $this->saveDb();
        }

        if (isset($_GET['unabuse'])) {
            $n = (int)$_GET['unabuse'];
            $c = $this->comments->get($n);
            if (count($c)) {
                $c['abuse'] = "0";
                $this->comments->edit($n, $c);
                $this->storageType == "xml" ? $this->saveXML() : $this->saveDb();
            }
        }

        if (isset($_GET['disable']) || isset($_GET['enable']) || isset($_GET['delete']) || isset($_GET['unabuse'])) {
            echo "<script type=\"text/javascript\">window.top.location.href='" . $this->posturl . "';</script>\n";
            exit();
        }

        echo "<div class=\"topic-comments\">\n";
        $c = $this->comments->getAll();
        if (count($c) > 0) {
            // Show the comments
            for ($i = 0; $i < count($c); $i++) {
                $comment = $c[$i];
                if (isset($comment['body'])) {
                    echo "<div class=\"topic-comment " . ($comment['approved'] == "1" ? "enabled" : "disabled") . ($comment['abuse'] == "1" ? " abused" : "") . "\">\n";
                    echo "\t<div class=\"topic-comments-user\">";
                    if ($comment['abuse'] == "1") {
                        echo "<img src=\"" . $this->basepath . "res/exclamation.png\" alt=\"Abuse\" title=\"" . l10n('admin_comment_abuse') . "\" style=\"vertical-align: middle;\">\n";
                    }
                    echo (stristr($comment['url'], "http") ? "<a href=\"" . $comment['url'] . "\" target=\"_blank\" " . (strpos($comment['url'], $imSettings['general']['url']) === false ? 'rel="nofollow"' : '') . ">" . $comment['name'] . "</a>" : $comment['name']);
                    if ($rating && isset($comment['rating']) && $comment['rating'] > 0) {
                        echo "\t<span class=\"topic-star-container-small\" title=\"" . $comment['rating'] . "/5\" style=\"margin-left: 5px; vertical-align: middle;\">
                                    <span class=\"topic-star-fixer-small\" style=\"width: " . round($comment['rating']/5 * 100) . "%;\"></span>
                            </span>\n";
                    }
                    echo "\t</div>\n";
                    echo "\t<div class=\"topic-comments-date imBreadcrumb\">" . $comment['timestamp'] . "</div>\n";
                    echo "\t<div class=\"topic-comments-body\">" . $comment['body'] . "</div>\n";
                    echo "\t<div class=\"topic-comments-controls\">\n";
                    echo "\t\t<span style=\"float: left;\">IP: " . $comment['ip'] . "</span>\n";
                    if ($comment['abuse'] == "1")
                        echo "\t\t<a href=\"" . $this->posturl . "unabuse=" . $i . "\">" . l10n("blog_abuse_remove", "Remove abuse") . "</a> |\n";
                    if ($comment['approved'] == "1")
                        echo "\t\t<a onclick=\"return confirm('" . str_replace("'", "\\'", l10n('blog_unapprove_question')) . "')\" href=\"" . $this->posturl . "disable=" . $i . "\">" . l10n('blog_unapprove') . "</a> |\n";
                    else
                        echo "\t\t<a onclick=\"return confirm('" . str_replace("'", "\\'", l10n('blog_approve_question')) . "')\" href=\"" . $this->posturl . "enable=" . $i . "\">" . l10n('blog_approve') . "</a> |\n";
                    echo "\t\t<a onclick=\"return confirm('" . str_replace("'", "\\'", l10n('blog_delete_question')) . "')\" href=\"" . $this->posturl . "delete=" . $i . "\">" . l10n('blog_delete') . "</a>\n";
                    echo "</div>\n";
                    echo "</div>\n";
                }
            }
        } else {
            echo "<div style=\"text-align: center; margin: 15px; 0\">" . l10n('blog_no_comment') . "</div>\n";
        }
        echo "</div>\n";        
    }

    /**
     * Show a single rating form
     * 
     * @return void
     */
    function showRating()
    {
        
        global $imSettings;

        if (isset($_POST['x5topicid']) && $_POST['x5topicid'] == $this->id && !isset($_COOKIE['vtd' . $this->id]) && isset($_POST['imJsCheck']) && $_POST['imJsCheck'] == 'jsactive') {
            $this->comments->add(
                array(
                    "rating" => $_POST['rating'],
                    "approved" => "1"
                )
            );
            $this->storageType == "xml" ? $this->saveXML() : $this->saveDb();
        }

        $c = $this->comments->getAll();
        $count = 0;
        $votes = 0;
        $vote = 0;
        if (count($c) > 0) {
            // Check aproved comments count
            $ca = array();
            foreach ($c as $comment) {
                if ($comment['approved'] == "1" && isset($comment['rating'])) {
                    $count++;
                    $votes += $comment['rating'];
                }
            }
            $vote = ($count > 0 ? $votes/$count : 0);
        }
        echo "
            <div style=\"text-align: center\">
                <div style=\"margin-bottom: 5px;\">" . l10n("blog_rating", "Vote:") . " " . number_format($vote, 1) . "/5</div>
                <div class=\"topic-star-container-big" . (!isset($_COOKIE['vtd' . $this->id]) ? " variable-star-rating" : "") . "\" data-url=\"" . $this->posturl . "\" data-id=\"" . $this->id . "\">
                    <span class=\"topic-star-fixer-big\" style=\"width: " . round($vote/5 * 100) . "%;\"></span>
                </div>
            </div>\n";
    }
}



/**
 * XML Handling class
 * @access public
 */
class imXML 
{
    var $tree = array();
    var $force_to_array = array();
    var $error = null;
    var $parser;
    var $inside = false;

    // PHP 5
    function __construct($encoding = 'UTF-8')
    {
        $this->setUp($encoding);
    }

    // PHP 4
    function imXML($encoding = 'UTF-8')
    {
        $this->setUp($encoding);   
    }

    function setUp($encoding = 'UTF-8')
    {
        $this->parser = xml_parser_create($encoding);
        xml_set_object($this->parser, $this); // $this was passed as reference &$this
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 1);
        xml_set_element_handler($this->parser, "startEl", "stopEl");
        xml_set_character_data_handler($this->parser, "charData");
        xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
    }

    function parse_file($file)
    {
        $fp = @fopen($file, "r");
        if (!$fp)
            return false;
        while ($data = fread($fp, 4096)) {
            if (!xml_parse($this->parser, $data, feof($fp))) {
                return false;
            }
        }
        fclose($fp);
        return $this->tree[0]["content"];
    }

    function parse_string($str)
    {
        if (!xml_parse($this->parser, $str))
            return false;
        if (isset($this->tree[0]["content"]))
            return $this->tree[0]["content"];
        return false;
    }

    function startEl($parser, $name, $attrs)
    {
        array_unshift($this->tree, array("name" => $name));
        $this->inside = false;
    }

    function stopEl($parser, $name)
    {
        if ($name != $this->tree[0]["name"])
            return false;
        if (count($this->tree) > 1) {
            $elem = array_shift($this->tree);
            if (isset($this->tree[0]["content"][$elem["name"]])) {
                if (is_array($this->tree[0]["content"][$elem["name"]]) && isset($this->tree[0]["content"][$elem["name"]][0])) {
                    array_push($this->tree[0]["content"][$elem["name"]], $elem["content"]);
                } else {
                    $this->tree[0]["content"][$elem["name"]] = array($this->tree[0]["content"][$elem["name"]],$elem["content"]);
                }
            } else {
                if (in_array($elem["name"], $this->force_to_array)) {
                    $this->tree[0]["content"][$elem["name"]] = array($elem["content"]);
                } else {
                    if (!isset($elem["content"])) $elem["content"] = "";
                    $this->tree[0]["content"][$elem["name"]] = $elem["content"];
                }
            }
        }
        $this->inside = false;
    }

    function charData($parser, $data)
    {
        if (!preg_match("/\\S/", $data))
                return false;
        if ($this->inside) {
            $this->tree[0]["content"] .= $data;
        } else {
            $this->tree[0]["content"] = $data;
        }
        $this->inside_data = true; 
    }
}


/**
 * Some useful functions
 *
 * @category X5engine
 * @package  X5engine
 * @license  Copyright by Incomedia http://incomedia.eu
 * @link     http://websitex5.com
 */

/**
 * Prints an error about not active JS
 *
 * @param $docType True to use the meta redirect with a complete document. False to use a javascript code.
 * 
 * @return void
 */
function imPrintJsError($docType = true)
{
    if ($docType) {
        $html = "<DOCTYPE><html><head><meta http-equiv=\"Refresh\" content=\"5;URL=" . $_SERVER['HTTP_REFERER'] . "\"></head><body>";
        $html .= l10n('form_js_error');
        $html .= "</body></html>";
    } else {
        $html = "<meta http-equiv=\"Refresh\" content=\"5;URL=" . $_SERVER['HTTP_REFERER'] . "\">";
        $html .= l10n('form_js_error');
    }
    return $html;
}

/**
 * Check the user's access to $page
 * 
 * @param string $page The page to check
 * 
 * @return void
 */
function imCheckAccess($page)
{
    $pa = new imPrivateArea();
    $stat = $pa->checkAccess($page);
    if ($stat !== 0) {
        $pa->savePage();
        header("Location: imlogin.php?loginstatus=" . $stat );
        exit;
    }
}


/**
 * Show the guestbook
 * This function is provided as compatibility for v9 guestbook widget
 * 
 * @param string  $id              The guestbook id
 * @param string  $filepath        The folder where the comments must be stored
 * @param string  $email           The email to notify the new comments
 * @param boolean $captcha         true to show the captcha
 * @param boolean $direct_approval true to directly approve comments
 * 
 * @return void
 */
function showGuestBook($id, $filepath, $email, $captcha = true, $direct_approval = true)
{
    global $imSettings;

    $gb = new ImTopic("gb" . $id);
    $gb->loadXML($filepath);
    $gb->showSummary(false);
    $gb->showForm(false, $captcha, !$direct_approval, $email, "guestbook", $imSettings['general']['url'] . "/admin/guestbook.php?id=" . $id);
    $gb->showComments(false);
}

/**
 * Provide the database connection data of given id
 * @param  string $dbid The database id
 * @return array        an array like array('description' => '', 'host' => '', 'database' => '', 'user' => '', 'password' => '')
 */
function getDbData($dbid) {
    global $imSettings;
    if (!isset($imSettings['databases'][$dbid]))
        return false;
    return $imSettings['databases'][$dbid];
}


/**
 * Shuffle an associate array
 * 
 * @param array $list The array to shuffle
 * 
 * @return array       The shuffled array
 */
function shuffleAssoc($list)
{
    if (!is_array($list))
        return $list;
    $keys = array_keys($list);
    shuffle($keys);
    $random = array();
    foreach ($keys as $key)
        $random[$key] = $list[$key];
    return $random;
}

/**
 * Provide a fallback for the PHP5 stripos function
 * 
 * @param string  $haystack Where to search
 * @param string  $needle   What to replace
 * @param integer $offset   Start searching from here
 * 
 * @return integer          The position of the searched string
 */
function imstripos($haystack, $needle , $offset = 0)
{
    if (function_exists('stripos')) // Is PHP5+
        return stripos($haystack, $needle, $offset);

    // PHP4 fallback
    return strpos(strtolower($haystack), strtolower($needle), $offset);
}

/**
 * Provide a localization helper
 * 
 * @param string $id      The localization key
 * @param string $default The default string
 * 
 * @return string          The localization
 */
function l10n($id, $default = "")
{
    global $l10n;

    if (!isset($l10n[$id]))
        return $default;

    return $l10n[$id];
}

/**
 * Combine paths
 * 
 * @param  array  $paths
 * 
 * @return string
 */
function pathCombine($paths = array())
{
    $s = array();
    foreach ($paths as $path) {
        if (strlen($path))
            $s[] = trim($path, "/\\ ");
    }
    return implode("/", $s);
}

/**
 * Try to convert a string to lowercase using multibyte encoding
 * 
 * @param  string $str
 * 
 * @return string
 */
function imstrtolower($str)
{
    return (function_exists("mb_convert_case") ? mb_convert_case($str, MB_CASE_LOWER, "UTF-8") : strtolower($str));
}

if (!function_exists('htmlspecialchars_decode')) {
    /**
     * Fallback for htmlspecialchars_decode in PHP4
     * @param  string  $text
     * @param  integer $quote_style
     * @return string
     */
    function htmlspecialchars_decode($text, $quote_style = ENT_COMPAT)
    {
        return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
    }
}

/**
 * Fallback for json_encode before PHP 5.2
 */
if (!function_exists('json_encode')) {
    function json_encode($data)
    {
        switch ($type = gettype($data)) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return ($data ? 'true' : 'false');
            case 'integer':
            case 'double':
            case 'float':
                return $data;
            case 'string':
                return '"' . addslashes($data) . '"';
            case 'object':
                $data = get_object_vars($data);
            case 'array':
                $output_index_count = 0;
                $output_indexed = array();
                $output_associative = array();
                foreach ($data as $key => $value) {
                    $output_indexed[] = json_encode($value);
                    $output_associative[] = json_encode($key) . ':' . json_encode($value);
                    if ($output_index_count !== NULL && $output_index_count++ !== $key) {
                        $output_index_count = NULL;
                    }
                }
                if ($output_index_count !== NULL) {
                    return '[' . implode(',', $output_indexed) . ']';
                } else {
                    return '{' . implode(',', $output_associative) . '}';
                }
            default:
                return ''; // Not supported
        }
    }
}

/**
 * Check for the valid data about spam and js
 * 
 * @param  string $prt The spam post field name
 * @param  string $js  The js post file name
 * 
 * @return bool
 */
function checkJsAndSpam($prt = 'prt', $js = 'imJsCheck')
{
    // Spam!
    if ($_POST[$prt] != "") {
        return false;
    }

    // Javascript disabled
    if (!isset($_POST[$js]) || $_POST[$js] != 'jsactive') {
        echo imPrintJsError(false);
        return false;
    }

    return true;
}

/**
 * Search if at least one element of $needle is in $haystack.
 * @param  Array   $needle   Non-associative array
 * @param  Array   $haystack Non-associative array
 * @param  boolean $all      Set to true to ensure that all the elements in $needle are in $haystack
 * @return boolean
 */
function in_array_field($needle, $haystack, $all = false)
{
    if ($all) {
        foreach ($needle as $key)
            if (!in_array($key, $haystack))
                return false;
        return true;
    } else {
        foreach ($needle as $key)
            if (in_array($key, $haystack))
                return true;
        return false;
    }
}

/**
 * Filter the var from unwanted input chars.
 * Basically remove the quotes added by magic_quotes
 * @param  mixed $var The var to filter
 * @return mixed      The filtered var
 */
function imFilterInput($var) {
    // Remove the magic quotes
    if (get_magic_quotes_gpc()) {        
        // String
        if (is_string($var))
            $var = stripslashes($var);
        // Array
        else if (is_array($var)) {
            for ($i = 0; $i < count($var); $i++)
                $var[$i] = imFilterInput($var[$i]);
        }
    }
    return $var;
}

/**
 * Provide a fallback function to get the headers array on PHP 4.3.0 and less.
 * 
 * @return array or FALSE on failure
 */
function imRequestHeaders()
{
    $headers = array();
    // If apache supports apache_request_headers, use it!
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (!is_array($headers))
            return false;
    } else {
        // Build the array manually
        foreach ($_SERVER as $key => $val) {
            if (strncmp($key, 'HTTP_', 5) === 0) {
                $headers[substr($key, 5)] = $_SERVER[$key];
            }
        }
        if (count($headers) === 0)
            return false;
        $headers['Content-Type'] = (isset($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : @getenv('CONTENT_TYPE');
    }

    // Format the headers name correctly. For example, turn CONTENT_TYPE into Content-Type.
    foreach ($headers as $key => $val) {
        $key = str_replace('_', ' ', strtolower($key));
        $key = str_replace(' ', '-', ucwords($key));
        $headers[$key] = $val;
    }
    
    return $headers;
}

// End of file