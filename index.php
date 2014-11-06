<?php

$data = curl_get_request('http://coursepress.lnu.se/kurser/');
$dom = new DomDocument();

getItems($dom,$data);

function getItems($dom,$data) {

    if($dom->loadHTML($data)){
        $xpath = new DOMXPath($dom);
        $items = $xpath->query('//ul[@id="blogs-list"]//div[@class="item-title"]/a');
        foreach($items as $item){

            echo $item->nodeValue . "-->" . $item->getAttribute('href') . "<br/>";
        }
    }
    nextPage($dom,$data);
}
function nextPage($dom,$data) {


        getItems($dom,"https://coursepress.lnu.se/kurser/?bpage=2");

   /* if($dom->loadHTML($data)){
        $xpath = new DOMXPath($dom);

        $nextPageUrl = $xpath->query("//div[@id='pag-bottom']/div[@class='pagination-links']/a[@href='']");
        var_dump($nextPageUrl->getAttribute('href'));
        }*/
}

function curl_get_request($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}