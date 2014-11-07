<meta charset="utf-8">
<?php

$data = curl_get_request('https://coursepress.lnu.se/kurser/');
$dom = new DomDocument();
$array = array();
getItems($dom,$data,$array);

function getItems($dom,$data,$array) {
    $urlArray = $array;

    if($dom->loadHTML($data)){
        $xpath = new DOMXPath($dom);
        $items = $xpath->query('//ul[@id="blogs-list"]//div[@class="item-title"]/a');
        foreach($items as $item){

            $courseCode = getCourseCode($dom,$item->getAttribute('href'));
            $coursePlan = getCoursePlan($dom,$item->getAttribute('href'));
            $courseEntryText = getCourseEntryText($dom,$item->getAttribute('href'));
            $coursePost = getLatestPost($dom,$item->getAttribute('href'));
            $urlArray[] = "<br/><br/> Coursename: ".$item->nodeValue . "--> Link: " . $item->getAttribute('href') . "--> Coursecode: " . $courseCode . "<br/> Courseplan: " . $coursePlan
            . "<br/>Introduktionstext: " . $courseEntryText ."<br/> Senaste inlägget: " . $coursePost ."<br/>";
        }
        $urlArray = array_map('utf8_encode', $urlArray);
        var_dump($urlArray);
    }
    nextPage($dom,$data,$urlArray);
}

function getLatestPost($dom,$courseURL) {
    $courseURL = curl_get_request($courseURL);
    if($dom->loadHTML($courseURL)){

        $xpath = new DOMXPath($dom);

        $getLatestPostTitle = $xpath->query('//header[@class="entry-header"]/h1[@class="entry-title"]')->item(0);
        $firstTitle = $getLatestPostTitle->firstChild;
        $title = $firstTitle->nodeValue;


        $getLatestPostTitle = $xpath->query('//header[@class="entry-header"]/p')->item(0);


        $authorDate = $getLatestPostTitle->textContent;
        if($title =! null){
            return $title . " | " . $authorDate;
        }else {
            return "Inga inlägg";
        }

    }
}

function getCourseEntryText($dom,$courseURL) {
    $courseURL = curl_get_request($courseURL);
    if($dom->loadHTML($courseURL)){

        $xpath = new DOMXPath($dom);

        $courseEntryText = $xpath->query('//div[@class="entry-content"]/p/text()')->item(0);
        if($courseEntryText != null) {
            return $courseEntryText->textContent;
        }else {
            return "No entry text";
        }
    }
}




function getCourseCode($dom,$courseURL) {
    $courseURL = curl_get_request($courseURL);
    if($dom->loadHTML($courseURL)){

        $xpath = new DOMXPath($dom);

        $courseCode = $xpath->query('//div[@id="header-wrapper"]/ul/li[last()]/a/text()')->item(0);

        if($courseCode != null){
            return $courseCode->textContent;
        }else {
            return "No coursecode";
        }
    }
}

function getCoursePlan($dom,$courseURL) {

    $courseURL = curl_get_request($courseURL);

    if($dom->loadHTML($courseURL)){

        $xpath = new DOMXPath($dom);

        $coursePlan = $xpath->query('//ul[@class="sub-menu"]/li/a/text()[contains(., "Kursplan")]')->item(0);

        $coursePLan = $coursePlan->parentNode;

        if($coursePLan != null){

            return $coursePLan->getAttribute('href');
        }else {

            return "No Courseplan";
        }
    }
}
function nextPage($dom,$data,$urlArray) {

    if($dom->loadHTML($data)){

        $xpath = new DOMXPath($dom);
        echo "<br/><br/><br/><br/><br/><br/><br/><br/><br/>";
        $nextPageUrl = $xpath->query("//div[@id='pag-bottom']/div[@class='pagination-links']/a[@class='next page-numbers']");

        foreach($nextPageUrl as $href){
            $nextPageUrl =  $href->getAttribute('href') . "<br/>";
        }

        $nextPageUrl =  curl_get_request("https://coursepress.lnu.se" . $nextPageUrl);

        if(strlen($nextPageUrl) > 0){
            getItems($dom,$nextPageUrl,$urlArray);
        }
    }
}

function curl_get_request($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}