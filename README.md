# stabi_booker
This script automatically books selected seats at Staatsbibliothek zu Berlin as soon as they become available.

## Installation
First, move `stabi_booker.php` a directory on your server. Second, adjust the configuration variables at the beginning of `stabi_booker.php` to ensure that the script signs in the right people. Third, set up a cronjob on your server that executes `stabi_booker.php` on a regular basis (say, in five minute intervals).
