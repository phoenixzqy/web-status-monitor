# Intro
a php based script to monitor give links/urls status. If any  error occurred, it could email an error list to you. You could run this with your corntab to have a scheduled status check. 

## Requirement

* php > 5.0
* cURL: `sudo apt-get install curl libcurl3 libcurl3-dev php5-curl`
    * Note: php[version]-curl, please use `php -v` to check out your php version before installing php-curl.
    * eg: if your php version is 7.2.10, use `php7.2-curl`
* sendmail: `sudo apt-get install sendmail`

## How to use

### execute once
you could just execute `sudo monitor.php` in terminal. It has to run under root, because it writes log to `/var/log/web_status_monitor.log`

Note: `chmod u+x monitor.php` if you have any permission problem. 
### add crontab job
in `sudo crontab -e`, add a new job, for example:
`0 * * * * * /your/path/to/monitor.php` 

## Config
Configuration is pretty straight forward in `config.json`, just add sites under websites, add email under emails.
