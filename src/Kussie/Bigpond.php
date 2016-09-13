<?php
namespace Kussie;

use Goutte\Client;
/**
 * Class for interacting with Telstra website.
 */
class Bigpond
{
    /**
     * @var integer
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $accountId;
    /**
     * @var string
     */
    protected $serviceId;
    /**
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $loginCrawler;
    /**
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $usageCrawler;
    /**
     * @var \Goutte\Client
     */
    protected $client;
    /**
     * @var string
     */
    protected $loginUrl = 'https://signon.telstra.com.au/login';
   /**
     * @var string
     */
    protected $usageUrl = 'https://www.my.telstra.com.au/myaccount/data-usage-internet?accountId=%s&serviceId=%s';

    /**
     * Creates a new Bigpond instance.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password, $accountId, $serviceId)
    {
        $this->username = $username;
        $this->password   = $password;
        $this->accountId = $accountId;
        $this->serviceId = $serviceId;
        $this->client     = new Client();
    }

    /**
     * Gets the Goutte Client for the scraper.
     *
     * @return \Goutte\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Logs the user in to the Telstra website.
     *
     * @return boolean sucessful login
     */
    public function login()
    {
        $crawler = $this->client->request(
            'POST',
            $this->loginUrl,
            [
                'username' => $this->username,
                'password'    => $this->password,
                'encoded' => false
            ]
        );
        $this->loginCrawler = $crawler;
        $this->usage();
    }

    /**
     * Gets the usage from the Telstra page
     *
     * @return boolean sucessful 
     */
    protected function usage()
    {
        if (!$this->loginCrawler) {
            $this->login();
        }

        $crawler = $this->client->request(
            'GET',
            sprintf($this->usageUrl, $this->accountId, $this->serviceId),
            []
        );
        $this->usageCrawler = $crawler;

        return true;
    }

    /**
     * Get the current Bigpond used quota
     *
     * @return string used quota
     */
    public function getUsed()
    {
        if (!$this->usageCrawler) {
            $this->login();
        }

        $used = $this->usageCrawler->filter('.usage-so-far span.number')->text();

        return $used;
    }

    /**
     * Get the current Bigpond remaining quota
     *
     * @return string used quota
     */
    public function getRemaining()
    {
        if (!$this->usageCrawler) {
            $this->login();
        }

        $remains = $this->usageCrawler->filter('.remains span.number')->text();

        return $remains;
    }

    /**
     * Get the current Bigpond days left
     *
     * @return string days left
     */
    public function getDaysLeft()
    {
        if (!$this->usageCrawler) {
            $this->login();
        }

        $daysLeft = $this->usageCrawler->filter('.days-remaining span.number')->text();

        return $daysLeft;
    }

    /**
     * Get the current Bigpond billing cycle
     *
     * @return string billing cycle
     */
    public function getPeriod()
    {
        if (!$this->usageCrawler) {
            $this->login();
        }

        $billingCycle = $this->usageCrawler->filter('.billing-dates span')->text();

        return trim($billingCycle);
    }

    public function getQuota()
    {
        if (!$this->usageCrawler) {
            $this->login();
        }

        $total = $this->usageCrawler->filter('.usage-desc-container span.allowance')->text();
        return str_replace('GB', '', $total);
        
    }
}
