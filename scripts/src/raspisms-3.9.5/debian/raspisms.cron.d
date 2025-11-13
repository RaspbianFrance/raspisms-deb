# Every minutes, check to renew expired quotas
* * * * * php /usr/share/raspisms/console.php controllers/internals/Console.php renew_quotas

# Every 5 minutes, check to do alerting on quota limits
*/5 * * * * php /usr/share/raspisms/console.php controllers/internals/Console.php quota_limit_alerting

# Every 15 minutes, check for phone reliability do alerting on quota limits
*/15 * * * * php /usr/share/raspisms/console.php controllers/internals/Console.php phone_reliability
