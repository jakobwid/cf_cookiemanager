<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;


/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022
 */

/**
 * The repository for Cookies
 */
class CookieRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * cookieServiceRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected CookieServiceRepository $cookieServiceRepository;


    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository
     */
    private ApiRepository $apiRepository;


    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository $apiRepository
     */
    public function injectApiRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository $apiRepository)
    {
        $this->apiRepository = $apiRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository
     */
    public function injectCookieServiceRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository)
    {
        $this->cookieServiceRepository = $cookieServiceRepository;
    }

    /**
     * @param $identifier
     */
    public function getCookieByName($identifier, $langUid = 0, $storage = [1])
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setLanguageUid($langUid)->setStoragePageIds($storage);
        $query->matching($query->logicalAnd($query->equals('name', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute();
    }

    public function insertFromAPI($langConfiguration,$offline = false)
    {
        foreach ($langConfiguration as $lang_config) {
            if (empty($lang_config)) {
                die("Invalid Typo3 Site Configuration");
            }
            foreach ($lang_config as $lang) {

                if(!$offline){
                    $cookies = $this->apiRepository->callAPI($lang["langCode"],"cookie");
                }else{
                    //offline call file
                    $cookies = $this->apiRepository->callFile($lang["langCode"],"cookie");
                }

                if(empty($cookies)){
                    return false;
                }


                foreach ($cookies as $cookie) {
                    if (empty($cookie["name"]) || empty($cookie["service_identifier"])) {
                        continue;
                    }
                    $cookieModel = new \CodingFreaks\CfCookiemanager\Domain\Model\Cookie();
                    $cookieModel->setPid($lang["rootSite"]);
                    $cookieModel->setName($cookie["name"]);
                    $cookieModel->setHttpOnly((int)$cookie["http_only"]);
                    if (!empty($cookie["path"])) {
                        $cookieModel->setPath($cookie["path"]);
                    }
                    if (!empty($cookie["secure"])) {
                        $cookieModel->setSecure($cookie["secure"]);
                    }
                    if (!empty($cookie["is_regex"])) {
                        $cookieModel->setIsRegex(true);
                    }
                    $cookieModel->setServiceIdentifier($cookie["service_identifier"]);
                    if (!empty($cookie["description"])) {
                        $cookieModel->setDescription($cookie["description"]);
                    } else {
                        $cookieModel->setDescription("");
                    }
                    //$cookieDB = $this->getCookieByName($cookie["name"]);
                    $cookieDB = $this->getCookieByName($cookie["name"], 0, [$lang["rootSite"]]); // $lang_config["languageId"]
                    if (count($cookieDB) == 0) {
                        $this->add($cookieModel);
                        $this->persistenceManager->persistAll();
                        $cookieUID = $cookieModel->getUid();

                        //If Cookie is needed by other Service create mm Table
                        $service = $this->cookieServiceRepository->getServiceByIdentifier($cookie["service_identifier"], $lang["language"]["languageId"], [$lang["rootSite"]]);
                        if (!empty($service[0]) && $lang["language"]["languageId"] == 0) {
                            $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
                            $sqlStr = "INSERT INTO tx_cfcookiemanager_cookieservice_cookie_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $service[0]->getUid() . "," . $cookieUID . ",0,0)";
                            $results = $con->executeQuery($sqlStr);
                        }
                    }

                    if($lang["language"]["languageId"] != 0){
                        $cookieDBOrigin = $this->getCookieByName($cookie["name"],0,[$lang["rootSite"]]); // $lang_config["languageId"]
                        $allreadyTranslated = $this->getCookieByName($cookie["name"],$lang["language"]["languageId"],[$lang["rootSite"]]);
                        if (count($allreadyTranslated) == 0) {
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookie');
                            $queryBuilder->insert('tx_cfcookiemanager_domain_model_cookie')->values([
                                'pid' => $lang["rootSite"],
                                'sys_language_uid' => $lang["language"]["languageId"],
                                'l10n_parent' => (int)$cookieDBOrigin[0]->getUid(),
                                'name' =>$cookie["name"],
                                'http_only' => (int)$cookie["http_only"],
                                'path' => $cookie["path"],
                                'secure' => $cookie["secure"],
                                'is_regex' => $cookie["is_regex"],
                                'service_identifier' => $cookie["service_identifier"],
                                'description' => $cookie["description"],
                            ])
                                ->executeStatement();
                        }

                        // * Get all Languages from a Service and create MM Table
                        $serviceTranslated = $this->cookieServiceRepository->getServiceByIdentifier($cookie["service_identifier"],  $lang["language"]["languageId"], [$lang["rootSite"]]);
                        if (!empty($serviceTranslated[0])) {
                            $suid = $serviceTranslated[0]->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                            //For Multi Language
                            $sqlStr = "INSERT INTO tx_cfcookiemanager_cookieservice_cookie_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $suid . "," . $cookieDBOrigin[0]->getUid() . ",0,0)";
                            $results = $con->executeQuery($sqlStr);
                        }

                    }
                }
            }
        }

        return true;
    }
}
