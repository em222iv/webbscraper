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
            $urlArray[] = $item->nodeValue . "-->" . $item->getAttribute('href') . "-->" . $courseCode . "<br/>" . $coursePlan ;

        }

    }
    nextPage($dom,$data,$urlArray);
}

function getCourseCode($dom,$courseURL) {
    $courseURL = curl_get_request($courseURL);
    if($dom->loadHTML($courseURL)){

        $xpath = new DOMXPath($dom);

        $courseCode = $xpath->query('//div[@id="header-wrapper"]/ul/li[last()]/a/text()')->item(0);

        return $courseCode->textContent;
    }
}

function getCoursePlan($dom,$courseURL) {

    $courseURL = curl_get_request($courseURL);

    if($dom->loadHTML($courseURL)){

        $xpath = new DOMXPath($dom);

        $coursePlan = $xpath->query('.//a/text()')->length == 8;
        var_dump($coursePlan);
        return $coursePlan->textContent;
    }


}
function nextPage($dom,$data,$urlArray) {

    if($dom->loadHTML($data)){

        $xpath = new DOMXPath($dom);
        echo "<br/><br/>";
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