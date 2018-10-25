#!/usr/bin/php
<?php
$config = json_decode(file_get_contents(__DIR__ . "/config.json"), true);
$logPath = "/var/log/web_status_monitor.log";
$curl = curl_init();

//  --------- helpers ----------
function errLog($msg)
{
    global $logPath;
    $message = "[Error]" . date("[y-m-d H:i:s]") . " $msg\n";
    error_log($message, 3, $logPath);
}
function infoLog($msg)
{
    global $logPath;
    $message = "[Info]" . date("[y-m-d H:i:s]") . " $msg\n";
    error_log($message, 3, $logPath);
}

function emailErrLog($email, $msg)
{
    $headers = "Reply-To: FDN-Web-Monitor <donotreply@fdn.monitor.fortinet.com>\r\n";
    $headers .= "Return-Path: FDN-Web-Monitor <donotreply@fdn.monitor.fortinet.com>\r\n";
    $headers .= "From: FDN-Web-Monitor <donotreply@fdn.monitor.fortinet.com>\r\n";
    $headers .= "Organization: Fortinet-FDN\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/plain; charset=iso-8859-1\r\n";
    $headers .= "X-Priority: 3\r\n";
    $headers .= "X-Mailer: PHP" . phpversion() . "\r\n";

    $message = "$msg \n\n\nThis is an auto-generated alert email from FDN-Web-Monitor.\nPlease do NOT reply to this email!!";
    try {
        mail(
            $email,
            "Web Status Monitor Error alert!!", // subject
            $message,
            $headers
        );
    } catch (\Exception $e) {
        errLog($e->getMessage());
    }
}
//  --------- end of helpers ----------
function startMonitor()
{
    global $config, $curl;
    $failedSites = [];
    // check sites
    foreach ($config['websites'] as $website) {
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_URL => $website['url'],
            CURLOPT_SSL_VERIFYPEER => 0, // important to make insecure connections
            CURLOPT_SSL_VERIFYHOST => 0, // important to make insecure connections

        ));
        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($result === false || $code >= 400) {
            errLog(
                "[Failed] " .
                "Website: " . $website['url'] . "; " .
                "Error Message: ($code) " . curl_error($curl)
            );
            $failedSites[] = [
                "website" => $website,
                "err_msg" => curl_error($curl),
                "code" => $code,
            ];
        } else {
            infoLog(
                "[Checked] " . $website['url']
            );
        }
    }
    if (count($failedSites) && count($config['emails'])) {
        // build email message
        $eMessage = "Below are failed sites: \n\n";
        foreach ($failedSites as $i => $fail) {
            $eMessage .= "$i.\n";
            $eMessage .= "\t[Title] " . $fail['website']['title'] . "\n" .
                "\t[Website]: " . $fail['website']['url'] . "\n" .
                "\t[Error] : (" . $fail['code'] . ") " . $fail['err_msg'] . "\n";
        }
        // send email
        foreach ($config['emails'] as $email) {
            emailErrLog($email, $eMessage);
        }
    }
}
// start monitoring once.
startMonitor();

// Releasing the cURL handler.
curl_close($curl);
