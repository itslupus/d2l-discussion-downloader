<?php
    require_once(__DIR__ . '/includes/classes/CURL.php');
    require_once(__DIR__ . '/globals.php');

    if ($_SERVER['argv'][1] == 1) {
        libxml_use_internal_errors(true);

        // create new cURL handler object
        $curlHandler = new CURL();

        // prepare to logon to UMLearn
        $curlHandler->setURL(GLOBAL_URL . '/d2l/lp/auth/login/login.d2l');
        $curlHandler->setPost(true);
        $curlHandler->setFields(array(
            'userName' => $_SERVER['argv'][2],
            'password' => $_SERVER['argv'][3]
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
echo("========================================\nINITIAL LOGIN SEQUENCE\n========================================\n[XSRF Token]\t\t$XSRF\n");

        // prepare to navigate to the course list page
        $curlHandler->setURL(GLOBAL_URL . '/d2l/le/manageCourses/search/6606');
        $curlHandler->setPost(false);

        // execute cURL request (will return HTML)
        $result = $curlHandler->execute();

        //DEBUG:
        // open a new file and write the data
        $newFile = fopen(__DIR__ . '/.tests/test-courseList.html', 'w');
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
        $curlHandler->setURL(GLOBAL_URL . '/d2l/le/manageCourses/search/6606/GridReloadPartial');
        $curlHandler->setPost(true);
        $curlHandler->setFields("gridPartialInfo\$_type=D2L.LP.Web.UI.Desktop.Controls.GridPartialArgs&gridPartialInfo\$SortingInfo\$SortField=OrgUnitName&gridPartialInfo\$SortingInfo\$SortDirection=0&gridPartialInfo\$NumericPagingInfo\$PageNumber=1&gridPartialInfo\$NumericPagingInfo\$PageSize=$maxPageSize&searchTerm=&status=-1&toStartDate\$Year=$date[0]&toStartDate\$Month=$date[1]&toStartDate\$Day=$date[2]&toStartDate\$Hour=9&toStartDate\$Minute=0&toStartDate\$Second=0&fromStartDate\$Year=$date[0]&fromStartDate\$Month=$date[1]&fromStartDate\$Day=$date[2]&fromStartDate\$Hour=9&fromStartDate\$Minute=0&fromStartDate\$Second=0&toEndDate\$Year=$date[0]&toEndDate\$Month=$date[1]&toEndDate\$Day=$date[2]&toEndDate\$Hour=9&toEndDate\$Minute=0&toEndDate\$Second=0&fromEndDate\$Year=$date[0]&fromEndDate\$Month=$date[1]&fromEndDate\$Day=$date[2]&fromEndDate\$Hour=9&fromEndDate\$Minute=0&fromEndDate\$Second=0&hasToStartDate=False&hasFromStartDate=False&hasToEndDate=False&hasFromEndDate=False&filtersFormId\$Value=$formID&_d2l_prc\$headingLevel=2&_d2l_prc\$scope&_d2l_prc\$childScopeCounters=filtersData:0;FromStartDate:0;ToStartDate:0;FromEndDate:0;ToEndDate:0&_d2l_prc\$hasActiveForm=false&filtersData\$semesterId=All&filtersData\$departmentId=All&isXhr=true&requestId=1&d2l_referrer=$XSRF");

        // execute request
        $result = $curlHandler->execute();
        // delete the first 9 characters of the result
        // for some reason, the response contains 'while(1);' at the very start before the json
        $result = substr($result, 9);
        
        //DEBUG:
        // open new file to write json to
        $newFile = fopen(__DIR__ . '/.tests/test-response.json', 'w');
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

echo("========================================\n$courseElement->textContent\n" . GLOBAL_URL . "/d2l/le/$courseID/discussions/List\n========================================\n");

            // prepare the jump to hyperspace
            $curlHandler->setURL(GLOBAL_URL . "/d2l/le/$courseID/discussions/List");
            $curlHandler->setPost(false);

            $result = $curlHandler->execute();

            //DEDUG:
            // save temporary discussion page
            $newFile = fopen(__DIR__ . "/.tests/test-$courseID.html", 'w');
            fwrite($newFile, $result);
            fclose($newFile);

            // reload the html/xpath and see if there are discussions
            $dom->loadHTML($result);
            $xpath = new DOMXPath($dom);

            // this element will only exist if there are no discussions available to the user
            $messageElement = $xpath->query('//div[@id = "ForumsTopicsPlaceholder"]/div/div[@class = "d2l-msg-container"]')->item(0);
            
            if ($messageElement !== null) {
echo("NO DISCUSSION FORUMS FOUND\n\n");
            } else {
                // get each forum element
                $forums = $xpath->query('//div[contains(@class, "d2l-forum-list-item")]');

                // iterate through each forum
                foreach($forums as $forumElement) {
                    // find the forum heading and subtext (if there is any)
                    $headingElement = $xpath->query('.//h2', $forumElement)->item(0);
                    $subtextElement = $xpath->query('.//div/div/div[contains(@class, "d2l-htmlblock")]', $forumElement)->item(0);
            
                    // topic table (this contains the all topics for this specific forum)
                    $tableElement = $xpath->query('.//d2l-table-wrapper/table', $forumElement->parentNode)->item(0);
                    // find each row in the table
                    $rows = $xpath->query('.//tr[contains(@class, "d2l-grid-row")]', $tableElement);
            
printf("%s (%s)\n",
    $headingElement->textContent,
    $subtextElement->textContent
);
            
                    // iterate through each of the rows (each topic)
                    foreach ($rows as $topic) {
                        // topic name
                        $topicElement = $xpath->query('.//a[@class = "d2l-linkheading-link d2l-clickable d2l-link"]', $topic)->item(0);
                        // topic subtext (if there is one)
                        $topicSubElement = $xpath->query('.//div[@class = "d2l-htmlblock d2l-htmlblock-deferred d2l-htmlblock-untrusted"]', $topic)->item(0);
                        // topic url
                        $topicURL = GLOBAL_URL . $topicElement->attributes[1]->textContent;

                        // helper variable to find the cells
                        $tdElements = $xpath->query('.//td[@class = "d2l-grid-cell"]', $topic);
                        // number of threads
                        $threadCountElement = $xpath->query('.//div[contains(@class, "d2l-textblock")]', $tdElements->item(0))->item(0);
                        // number of posts
                        $postCountElement = $xpath->query('.//div[contains(@class, "d2l-textblock")]', $tdElements->item(1))->item(0);
            
printf("\t%s (%s)\n\t%s\n",
    $topicElement->textContent,
    $topicSubElement->textContent,
    $topicURL
);

                        if ($threadCountElement->textContent > 0) {
                            // the number of results (threads) to return in this query
                            $numResults = 1000;

                            $url = str_replace('View', 'ThreadList', $topicURL);
                            $params = "?inContentTool=False&pageSize=$numResults&pageNumber=1&checkPageNumber=false&isNoneSelected=true&groupFilterOption=0&_d2l_prc\$headingLevel=1&_d2l_prc\$hasActiveForm=false&isXhr=true&requestId=1";
                            $curlHandler->setURL($url . $params);
                            $curlHandler->setPost(false);
                            
                            // again, we remove the while(1); from the result
                            $result = $curlHandler->execute();
                            $result = substr($result, 9);

                            //DEBUG:
                            $tempName = explode('/', $topicURL);
                            $file = fopen(__DIR__ . "/.tests/test-$tempName[5]-$tempName[8].json", 'w');
                            fwrite($file, $result);
                            fclose($file);
                            
                            //https://universityofmanitoba.desire2learn.com/d2l/le/$var1/discussions/threads/$var2/PostList?inContentTool=False&pageSize=$numPosts&pageNumber=1&checkPageNumber=false&filters=&isNoneSelected=true&searchText=&markedUnread=false&_d2l_prc\$headingLevel=1&_d2l_prc\$scope=&_d2l_prc\$hasActiveForm=false&isXhr=true&requestId=1
                            
                            // now we get the thread replies
                            $json1 = json_decode($result, false);

                            $dom1 = new DOMDocument();
                            $dom1->loadHTML($json1->Payload->Html);
                            $xpath1 = new DOMXPath($dom1);
                            
                            $threads = $xpath1->query('//li[contains(@class, "d2l-datalist-simpleitem")]');
$numThreads = count($threads);
$currCount = 1;
                            foreach ($threads as $threadItem) {
                                $threadElement = $xpath1->query('.//div/div/div/div/div/div/div/h1/a', $threadItem)[0];
                                
                                $threadName = $threadElement->textContent;
                                $threadLink = GLOBAL_URL . $threadElement->attributes[1]->textContent;
                                
                                $threadDetailsElement = $xpath1->query('.//div/div/div/div/div/div/div[contains(@class, "d2l-textblock-secondary")]', $threadItem)->item(0);
                                // $threadContentElement = $xpath1->query('.//div/div/div/div/div/d2l-more-less/div/div/template', $threadItem)[0];

                                // number of posts to get for this thread
                                $numPosts = 100;
                                // course ID we can reuse from before
                                // thread ID
                                $threadID = explode('/', $threadElement->attributes[1]->textContent)[6];

echo("\t\t=> DOWNLOADING $currCount of $numThreads\n"); $currCount++;
echo("\t\t   $threadName\n\t\t   $threadDetailsElement->textContent\n\t\t   $threadLink\n");

                                $curlHandler->setURL("https://universityofmanitoba.desire2learn.com/d2l/le/$courseID/discussions/threads/$threadID/PostList?inContentTool=False&pageSize=$numPosts&pageNumber=1&checkPageNumber=false&filters=&isNoneSelected=true&searchText=&markedUnread=false&_d2l_prc\$headingLevel=1&_d2l_prc\$scope=&_d2l_prc\$hasActiveForm=false&isXhr=true&requestId=1");
                                $curlHandler->setPost(false);
                                $result = $curlHandler->execute();
                                $result = substr($result, 9);

                                //DEBUG:
                                if (is_dir(__DIR__ . "/.tests/$courseID") === false) {
                                    mkdir(__DIR__ . "/.tests/$courseID");
                                }

                                if (is_dir(__DIR__ . "/.tests/$courseID/$tempName[8]") === false) {
                                    mkdir(__DIR__ . "/.tests/$courseID/$tempName[8]");
                                }

                                $file = fopen(__DIR__ . "/.tests/$courseID/$tempName[8]/test-$threadID.json", 'w');
                                fwrite($file, $result);
                                fclose($file);
                            }
                        }
                    }
                }
            }
        }

        //TODO: logout here
        //https://universityofmanitoba.desire2learn.com/d2l/logout
echo("\n==========\nLOGGED OUT\n==========");

        $curlHandler->setURL('https://universityofmanitoba.desire2learn.com/d2l/le/358593/discussions/threads/716699/View');
        $curlHandler->setPost(false);
        $result = $curlHandler->execute();

        $file = fopen(__DIR__ . '/.tests/test-manual.html', 'w');
        fwrite($file, $result);
        fclose($file);
    } else {
        /*
                OFFLINE CONTENT GOES HERE
                - Whatever lives inside this else will not touch the cURL logic above
        */

        $file = fopen(__DIR__ . '/.tests/test-359688-105977.json', 'r');
        $contents = fread($file, filesize('.tests/test-359688-105977.json'));
        fclose($file);
    
        $json = json_decode($contents, false);

        $dom = new DOMDocument();
        $dom->loadHTML($json->Payload->Html);
        $xpath = new DOMXPath($dom);
        
        $threads = $xpath->query('//li[contains(@class, "d2l-datalist-simpleitem")]');
        
        foreach ($threads as $threadItem) {
            $threadElement = $xpath->query('.//div/div/div/div/div/div/div/h1/a', $threadItem)[0];
            
            $threadName = $threadElement->textContent;
            $threadLink = GLOBAL_URL . $threadElement->attributes[1]->textContent;
            
            $threadDetailsElement = $xpath->query('.//div/div/div/div/div/div/div[contains(@class, "d2l-textblock-secondary")]', $threadItem)->item(0);
            $threadAuthor = $threadDetailsElement;

            $threadContentElement = $xpath->query('.//div/div/div/div/div/d2l-more-less/div/div/template', $threadItem)[0];

            echo("$threadName\n\t=> $threadLink\n\t=> $threadDetailsElement->textContent\n\t\t=> $threadContentElement->textContent\n");

            // create a new document and iterate through the list of elements in <template>
            // $newDoc = new DOMDocument();
            // foreach($threadContentElement->childNodes as $child) {
            //     // add each child to the new document
            //     // importNode(true) will include the children of this child
            //     $newDoc->appendChild($newDoc->importNode($child, true));
            // }

            // // print the innerHTML of <template>
            // echo($newDoc->saveHTML() . "=====\n");
        }
    }
?>