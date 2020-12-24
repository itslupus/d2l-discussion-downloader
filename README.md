# D2L Discussion Downloader
This project used to be part of something larger but I abandoned it (like all my other projects). The only usable part of this project saves the HTML of all discussions it finds in a JSON file (which is retrieved from D2L).  

You can view the other files that were in this repository in the commit history.  

This project is not affilated with Brightspace or the University of Manitoba.

### Requirements
This project is written for PHP 7. The following extensions are required:
- `xml`

### Usage
```
php -f test.php 1 <umnetid> <password>
```
The files will be placed in a hidden directory `.tests` in the same folder of the script.
