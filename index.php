<?php
//hämtar ut tid och visar användaren knapparna för att använda skrapan
$date = time();
echo "<form method='post'>";
echo "<input type='submit' name='submit' value='JSON-file'>";
echo "</form>";
echo "<form method='post'>";
echo "<input type='submit' name='submit1' value='Scrape'>";
echo "</form>";
//gör en request
$data = curl_get_request('https://coursepress.lnu.se/kurser/');
$dom = new DomDocument();
//skriver in timestamp till arrayen, för att se då skrapninge gjordes
$array = array("time" => "Tid för senaste skrapningen: " .$date."<br/><br/>");

//ser ifall det har gått 5 min sen senaste skrapningen, isåfall, gör en ny skrapningen
$p = json_decode(file_get_contents('filename.json'));
if($p->{"time"} <= time() - (60*5)){
    getItems($dom,$data,$array);
}
//ser ifall användaren vill se vad som skrapats eller ifall denne vill göra en nu skrapning
if(isset($_POST['submit'])) {
    $p = json_decode(file_get_contents('filename.json'));
    print_r($p);
}
if(isset($_POST['submit1'])) {
    getItems($dom,$data,$array);
}


//metod för att loopa igenom alla sidor på coursepresssidan
function getItems($dom,$data,$array) {
    $urlArray = $array;

    if($dom->loadHTML($data)){
        $xpath = new DOMXPath($dom);
        $items = $xpath->query('//ul[@id="blogs-list"]//div[@class="item-title"]/a');

        //loopen som sammanställer all data
        foreach($items as $item){
            //om det är en kurssida, hämta ut informationen ur den till arrayen
            if(substr($item->getAttribute('href'), 0, 31) == "https://coursepress.lnu.se/kurs"){
                //kurskod
                $courseCode = getCourseCode($dom,$item->getAttribute('href'));
                //kursplans länk
                $coursePlan = getCoursePlan($dom,$item->getAttribute('href'));
                //introduktionstext
                $courseEntryText = getCourseEntryText($dom,$item->getAttribute('href'));
                //första posten på sidan
                $coursePost = getLatestPost($dom,$item->getAttribute('href'));
                //skicka in arrayer med info i arrayen för att kunna få en JSON-struktur senare
                $urlArray[] = array( "Coursename: " => $item->nodeValue, "Länk: " => $item->getAttribute('href'), "Coursecode: " => $courseCode , "Courseplan: " => $coursePlan,
                "Introduktionstext: " => $courseEntryText, "Senaste inlägget: " => $coursePost."<br/><br/>");
            }
        }
    }
    //kolla nästa sida
    nextPage($dom,$data,$urlArray);
}
//senaste posten hämtas ut
function getLatestPost($dom,$courseURL) {
    $courseURL = curl_get_request($courseURL);
    if($dom->loadHTML($courseURL)){
        $xpath = new DOMXPath($dom);

        $getLatestPostTitle = $xpath->query('//header[@class="entry-header"]/h1[@class="entry-title"]')->item(0);
        $firstTitle = $getLatestPostTitle->firstChild;
        $title = $firstTitle->nodeValue;

        $getLatestPostTitle = $xpath->query('//header[@class="entry-header"]/p')->item(0);
        $authorDate = $getLatestPostTitle->textContent;


            return $title . " | " . $authorDate;
    }
}
//introduktionstexten hämtas här
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
//kurskoden hämtas här
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
//kursplanen hämtas här
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
// går till nästa sida ifall det finns en
function nextPage($dom,$data,$urlArray) {
    if($dom->loadHTML($data)){
        $xpath = new DOMXPath($dom);

        $nextPageUrl = $xpath->query("//div[@id='pag-bottom']/div[@class='pagination-links']/a[@class='next page-numbers']");

        foreach($nextPageUrl as $href){
            $nextPageUrl =  $href->getAttribute('href') . "<br/>";
        }

        //om det inte finns en ny sida, gå till metod för att spara ner till JSON-fil
        if($nextPageUrl == 1){
            saveJSON($urlArray);
        }

        $nextPageUrl = curl_get_request("https://coursepress.lnu.se" . $nextPageUrl);

        if(strlen($nextPageUrl) > 0){
            getItems($dom,$nextPageUrl,$urlArray);
        }
    }
}
//sparar en arrayen till objekt för att sedan spara ner det till JSON-format
function saveJSON($urlArray) {
    $amountOfCourses = count($urlArray);
    array_unshift($urlArray, "Kurser just nu: ".($amountOfCourses= $amountOfCourses-1));
    $JSON = (object) $urlArray;
    file_put_contents('filename.json', json_encode($JSON));
    $JSON = json_decode(file_get_contents('filename.json'));
    print_r($JSON);

}
//metod för att göra requesten till servern och identifierar sig som em222iv
function curl_get_request($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: em222iv"));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}