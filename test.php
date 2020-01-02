<?php
    require_once(__DIR__ . '/includes/classes/CURL.php');
    require_once(__DIR__ . '/globals.php');

    $LOGIN = 1;

    if ($LOGIN) {
        libxml_use_internal_errors(true);

        // create new cURL handler object
        $curlHandler = new CURL();

        // prepare to logon to UMLearn
        $curlHandler->setURL('https://universityofmanitoba.desire2learn.com/d2l/lp/auth/login/login.d2l');
        $curlHandler->setPost(true);
        $curlHandler->setFields(array(
            'userName' => $_SERVER['argv'][1],
            'password' => $_SERVER['argv'][2]
        ));

        // execute cURL request
        $result = $curlHandler->execute();

        // make sure we didnt error out or anything, if we did just stop script execution
        if ($result === false) {
            exit(0);
        }
        
        //TODO: check here for login success/failure

        // get the XSRF token for D2L/UMLearn specific requests
        // is this allowed? ¯\_(ツ)_/¯
        $XSRF;
        preg_match('/XSRF\.Token\',\'(.*?)\'/', $result, $XSRF);
        $XSRF = $XSRF[1];
echo("[XSRF Token]\t\t$XSRF\n");

        // prepare to navigate to the course list page
        $curlHandler->setURL('https://universityofmanitoba.desire2learn.com/d2l/le/manageCourses/search/6606');
        $curlHandler->setPost(false);

        // execute cURL request (will return HTML)
        $result = $curlHandler->execute();

        //DEBUG:
        // open a new file and write the data
        $newFile = fopen(__DIR__ . '/test-courseList.html', 'w');
        fwrite($newFile, $result);
        fclose($newFile);

        // now we have to find the id of the form in the HTML
        // for some reason, the next call to their API fails without it, wasting ~28kb of bandwidth

        // create new DOM object from the previous $result
        $dom = new DOMDocument();
        $dom->loadHTML($result);
        // using the DOM object we create the node tree
        $xpath = new DOMXPath($dom);
        // query for that form element, this will always exist
        $formElement = $xpath->query('//div[@class="d2l-form d2l-form-nested"]');

        // now we have to gather the data to send along with the request, formID being one of them
        // get today's date to send along, we are fetching all courses upto today
        // include the max results/"page" (UMLearn dropdown max appears to be 100, but it seems to accept larger)
        $formID = $formElement->item(0)->getAttribute('id');
        $date = explode(' ', date('Y n j'));
        $maxPageSize = 500;

echo("[Form ID]\t\t\t$formID\n");
print_r($date);
echo("[Max Results]\t\t$maxPageSize\n\n");

        // prepare to fetch all courses
        $curlHandler->setURL('https://universityofmanitoba.desire2learn.com/d2l/le/manageCourses/search/6606/GridReloadPartial');
        $curlHandler->setPost(true);
        $curlHandler->setFields("gridPartialInfo\$_type=D2L.LP.Web.UI.Desktop.Controls.GridPartialArgs&gridPartialInfo\$SortingInfo\$SortField=OrgUnitName&gridPartialInfo\$SortingInfo\$SortDirection=0&gridPartialInfo\$NumericPagingInfo\$PageNumber=1&gridPartialInfo\$NumericPagingInfo\$PageSize=$maxPageSize&searchTerm=&status=-1&toStartDate\$Year=$date[0]&toStartDate\$Month=$date[1]&toStartDate\$Day=$date[2]&toStartDate\$Hour=9&toStartDate\$Minute=0&toStartDate\$Second=0&fromStartDate\$Year=$date[0]&fromStartDate\$Month=$date[1]&fromStartDate\$Day=$date[2]&fromStartDate\$Hour=9&fromStartDate\$Minute=0&fromStartDate\$Second=0&toEndDate\$Year=$date[0]&toEndDate\$Month=$date[1]&toEndDate\$Day=$date[2]&toEndDate\$Hour=9&toEndDate\$Minute=0&toEndDate\$Second=0&fromEndDate\$Year=$date[0]&fromEndDate\$Month=$date[1]&fromEndDate\$Day=$date[2]&fromEndDate\$Hour=9&fromEndDate\$Minute=0&fromEndDate\$Second=0&hasToStartDate=False&hasFromStartDate=False&hasToEndDate=False&hasFromEndDate=False&filtersFormId\$Value=$formID&_d2l_prc\$headingLevel=2&_d2l_prc\$scope&_d2l_prc\$childScopeCounters=filtersData:0;FromStartDate:0;ToStartDate:0;FromEndDate:0;ToEndDate:0&_d2l_prc\$hasActiveForm=false&filtersData\$semesterId=All&filtersData\$departmentId=All&isXhr=true&requestId=1&d2l_referrer=$XSRF");

        // execute request
        $result = $curlHandler->execute();
        // delete the first 9 characters of the result
        // for some reason, the response contains 'while(1);' at the very start before the json
        $result = substr($result, 9);
        
        //DEBUG:
        // open new file to write json to
        $newFile = fopen(__DIR__ . '/test-response.json', 'w');
        fwrite($newFile, $result);
        fclose($newFile);

        // start decoding the JSON to get the links to the courses
        $json = json_decode($result, false);

        // so the response contains HTML (presumably the HTML that would replace the existing stuff in that form)
        // we are interested in that HTML
        $dom->loadHTML($json->Payload->Html);
        $xpath = new DOMXPath($dom);

        // get all the links in that HTML (assuming that all links here are links to course pages)
        $href = $xpath->query('//a[@class = "d2l-link"]');

        // iterate though all the links
        foreach ($href as $courseElement) {
            // get the course ID of the link (/d2l/p/home/xxxxxx)
            // we only need the ID since we can go straight to the dicussions of the course
            $courseID = explode('/', $courseElement->getAttribute('href'))[4];

echo("$courseElement->textContent\n");
echo("\t=> https://universityofmanitoba.desire2learn.com/d2l/le/$courseID/discussions/List\n");
            // prepare the jump to hyperspace
            $curlHandler->setURL("https://universityofmanitoba.desire2learn.com/d2l/le/$courseID/discussions/List");
            $curlHandler->setPost(false);

            $result = $curlHandler->execute();

            //DEDUG:
            // save temporary discussion page
            $newFile = fopen(__DIR__ . "/test-$courseID.html", 'w');
            fwrite($newFile, $result);
            fclose($newFile);

            // reload the html/xpath and see if there are discussions
            $dom->loadHTML($result);
            $xpath = new DOMXPath($dom);

            $messageElement = $xpath->query('//div[@id = "ForumsTopicsPlaceholder"]/div/div[@class = "d2l-msg-container"]')->item(0);
            if ($messageElement !== null) {
echo("\t=> this course has no discussion forum(s)\n");
            } else {
                $forums = $xpath->query('//div[contains(@class, "d2l-forum-list-item")]');
                foreach($forums as $forumElement) {
                    // forum heading stuff
                    $headingElement = $xpath->query('.//h2', $forumElement)->item(0);
                    $subtextElement = $xpath->query('.//div/div/div[contains(@class, "d2l-htmlblock")]', $forumElement)->item(0);
            
                    // topic table (this contains the topics for this specific forum)
                    $tableElement = $xpath->query('.//d2l-table-wrapper/table', $forumElement->parentNode)->item(0);
                    $rows = $xpath->query('.//tr[contains(@class, "d2l-grid-row")]', $tableElement);
            
                    printf("\t=> %s (%s)\n",
                        $headingElement->textContent,
                        $subtextElement->textContent
                    );
            
                    foreach ($rows as $topic) {
                        // topic name
                        $topicElement = $xpath->query('.//a[@class = "d2l-linkheading-link d2l-clickable d2l-link"]', $topic)->item(0);
                        // topic subtext
                        $topicSubElement = $xpath->query('.//div[@class = "d2l-htmlblock d2l-htmlblock-deferred d2l-htmlblock-untrusted"]', $topic)->item(0);
                        
                        // helper variable
                        $tdElements = $xpath->query('.//td[@class = "d2l-grid-cell"]', $topic);
                        // number of threads
                        $threadCountElement = $xpath->query('.//div[contains(@class, "d2l-textblock")]', $tdElements->item(0))->item(0);
                        // number of posts
                        $postCountElement = $xpath->query('.//div[contains(@class, "d2l-textblock")]', $tdElements->item(1))->item(0);
            
                        printf("\t\t=> %s\n\t\t=> %s (%s)\n\t\t\t=> %d threads\n\t\t\t=> %d posts\n",
                            GLOBAL_URL . $topicElement->attributes[1]->textContent,
                            $topicElement->textContent,
                            $topicSubElement->textContent,
                            $threadCountElement->textContent,
                            $postCountElement->textContent
                        );
                    }
                }
            }
        }

        //TODO: logout here
        //https://universityofmanitoba.desire2learn.com/d2l/logout
echo("\n==========\nLOGGED OUT\n==========");
    } else {
        /*
                OFFLINE CONTENT GOES HERE
                - Whatever lives inside this else will not touch the cURL logic above
        */

        $file = fopen(__DIR__ . '/test-359688.html', 'r');
        $contents = fread($file, filesize('test-359688.html'));
        fclose($file);
    
        $dom = new DOMDocument();
        $dom->loadHTML($contents);
        $xpath = new DOMXPath($dom);
        
        $forums = $xpath->query('//div[contains(@class, "d2l-forum-list-item")]');
        foreach($forums as $forumElement) {
            // forum heading stuff
            $headingElement = $xpath->query('.//h2', $forumElement)->item(0);
            $subtextElement = $xpath->query('.//div/div/div[contains(@class, "d2l-htmlblock")]', $forumElement)->item(0);
    
            // topic table (this contains the topics for this specific forum)
            $tableElement = $xpath->query('.//d2l-table-wrapper/table', $forumElement->parentNode)->item(0);
            $rows = $xpath->query('.//tr[contains(@class, "d2l-grid-row")]', $tableElement);
    
            printf("%s (%s)\n",
                $headingElement->textContent,
                $subtextElement->textContent
            );
    
            foreach ($rows as $topic) {
                // topic name
                $topicElement = $xpath->query('.//a[@class = "d2l-linkheading-link d2l-clickable d2l-link"]', $topic)->item(0);
                // topic subtext
                $topicSubElement = $xpath->query('.//div[@class = "d2l-htmlblock d2l-htmlblock-deferred d2l-htmlblock-untrusted"]', $topic)->item(0);
                
                // helper variable
                $tdElements = $xpath->query('.//td[@class = "d2l-grid-cell"]', $topic);
                // number of threads
                $threadCountElement = $xpath->query('.//div[contains(@class, "d2l-textblock")]', $tdElements->item(0))->item(0);
                // number of posts
                $postCountElement = $xpath->query('.//div[contains(@class, "d2l-textblock")]', $tdElements->item(1))->item(0);
    
                printf("\t%s\n\t=> %s (%s)\n\t\t=> %d threads and %d posts\n",
                    $topicElement->attributes[1]->textContent,
                    $topicElement->textContent,
                    $topicSubElement->textContent,
                    $threadCountElement->textContent,
                    $postCountElement->textContent
                );
            }
        }
    }
?>