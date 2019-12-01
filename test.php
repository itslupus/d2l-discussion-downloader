<?php
    $token;
    $DEBUG = 0;

    if ($DEBUG) {
        $tmpFile = tmpfile();
        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];
        echo($tmpFilePath . "\n");

        // login
        $curl = curl_init('https://universityofmanitoba.desire2learn.com/d2l/lp/auth/login/login.d2l');
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array(
            'userName' => $_SERVER['argv'][1],
            'password' => $_SERVER['argv'][2],
            'loginPath' => '/d2l/login'
        ));
        curl_setopt($curl, CURLOPT_COOKIEJAR, $tmpFilePath);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $tmpFilePath);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux i586; rv:31.0) Gecko/20100101 Firefox/70.0');
        
        // get the XSRF token for requests
        $match;
        preg_match("/XSRF\.Token\',\'(.*?)\'/", curl_exec($curl), $match);
        $token = $match[1];
        echo($token . "\n");

        // fetch the courses
        curl_setopt($curl, CURLOPT_URL, 'https://universityofmanitoba.desire2learn.com/d2l/le/manageCourses/search/6606');
        $result = curl_exec($curl);
        $courseList = fopen(__DIR__ . '/test-courseList.html', 'w+');
        fwrite($courseList, $result);
        fclose($courseList);

        // find the id of the form
        // unfortunately, we have to waste ~28kb of bandwidth to do this as the api will fail without it
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($result);
        $query = new DOMXPath($dom);
        $nodes = $query->query('//div[@class="d2l-form d2l-form-nested"]');
        $id = $nodes->item(0)->getAttribute('id');
        echo($id . "\n");

        $date = explode(' ', date("Y n j"));
        $maxPageSize = 100;
        // actually fetch courses
        curl_setopt($curl, CURLOPT_URL, 'https://universityofmanitoba.desire2learn.com/d2l/le/manageCourses/search/6606/GridReloadPartial');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "gridPartialInfo\$_type=D2L.LP.Web.UI.Desktop.Controls.GridPartialArgs&gridPartialInfo\$SortingInfo\$SortField=OrgUnitName&gridPartialInfo\$SortingInfo\$SortDirection=0&gridPartialInfo\$NumericPagingInfo\$PageNumber=1&gridPartialInfo\$NumericPagingInfo\$PageSize=$maxPageSize&searchTerm=&status=-1&toStartDate\$Year=$date[0]&toStartDate\$Month=$date[1]&toStartDate\$Day=$date[2]&toStartDate\$Hour=9&toStartDate\$Minute=0&toStartDate\$Second=0&fromStartDate\$Year=$date[0]&fromStartDate\$Month=$date[1]&fromStartDate\$Day=$date[2]&fromStartDate\$Hour=9&fromStartDate\$Minute=0&fromStartDate\$Second=0&toEndDate\$Year=$date[0]&toEndDate\$Month=$date[1]&toEndDate\$Day=$date[2]&toEndDate\$Hour=9&toEndDate\$Minute=0&toEndDate\$Second=0&fromEndDate\$Year=$date[0]&fromEndDate\$Month=$date[1]&fromEndDate\$Day=$date[2]&fromEndDate\$Hour=9&fromEndDate\$Minute=0&fromEndDate\$Second=0&hasToStartDate=False&hasFromStartDate=False&hasToEndDate=False&hasFromEndDate=true&filtersFormId\$Value=$id&_d2l_prc\$headingLevel=2&_d2l_prc\$scope&_d2l_prc\$childScopeCounters=filtersData:0;FromStartDate:0;ToStartDate:0;FromEndDate:0;ToEndDate:0&_d2l_prc\$hasActiveForm=false&filtersData\$semesterId=All&filtersData\$departmentId=All&isXhr=true&requestId=4&d2l_referrer=$token");
        $result = curl_exec($curl);
        $courses = fopen(__DIR__ . '/test-courses.json', 'w+');
        fwrite($courses, $result);
        fclose($courses);

        curl_close($curl);
        fclose($tmpFile);
    }

    // require_once 'mpdf/autoload.php';
    // $mpdf = new \Mpdf\Mpdf();
    // $mpdf->WriteHTML('hello world');
    // $mpdf->Output('test.pdf', 'F');

    $fileName = 'test-courses.json';
    $fileSize = filesize($fileName);
    $filePtr = fopen($fileName, "r");
    $fileContents = fread($filePtr, $fileSize);
    $fileContents = substr($fileContents, -($fileSize - 9));
    fclose($filePtr);

    $json = json_decode($fileContents, false);
    
    $dom = new DOMDocument();
    $dom->loadHTML($json->Payload->Html);
    $xpath = new DOMXPath($dom);

    $elements = $xpath->query('//a[@class = "d2l-link"]');

    foreach ($elements as $el) {
        echo("Name: $el->textContent\n\t" . $el->getAttribute('href') . "\n");
    }
?>