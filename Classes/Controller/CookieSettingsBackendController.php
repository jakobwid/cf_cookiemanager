<?php

namespace CodingFreaks\CfCookiemanager\Controller;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Service\AutoconfigurationService;
use CodingFreaks\CfCookiemanager\Updates\StaticDataUpdateWizard;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
//use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;


/**
 * CFCookiemanager Backend module Controller
 */
class CookieSettingsBackendController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected CookieCartegoriesRepository $cookieCartegoriesRepository;
    protected CookieServiceRepository $cookieServiceRepository;
    protected CookieFrontendRepository $cookieFrontendRepository;
    protected CookieRepository $cookieRepository;
    protected ScansRepository $scansRepository;
    protected PersistenceManager  $persistenceManager;
    protected VariablesRepository  $variablesRepository;
    protected ModuleTemplateFactory   $moduleTemplateFactory;
    protected Typo3Version $version;
    protected AutoconfigurationService $autoconfigurationService;
    public array $tabs = [];

    public function __construct(
        PageRenderer                $pageRenderer,
        CookieCartegoriesRepository $cookieCartegoriesRepository,
        CookieFrontendRepository    $cookieFrontendRepository,
        CookieServiceRepository     $cookieServiceRepository,
        CookieRepository            $cookieRepository,
        IconFactory                 $iconFactory,
        ScansRepository             $scansRepository,
        PersistenceManager          $persistenceManager,
        VariablesRepository         $variablesRepository,
        ModuleTemplateFactory       $moduleTemplateFactory,
        Typo3Version                $version,
        AutoconfigurationService    $autoconfigurationService
    )
    {
        $this->pageRenderer = $pageRenderer;
        $this->cookieCartegoriesRepository = $cookieCartegoriesRepository;
        $this->cookieServiceRepository = $cookieServiceRepository;
        $this->cookieFrontendRepository = $cookieFrontendRepository;
        $this->iconFactory = $iconFactory;
        $this->cookieRepository = $cookieRepository;
        $this->scansRepository = $scansRepository;
        $this->persistenceManager = $persistenceManager;
        $this->variablesRepository = $variablesRepository;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->version = $version;
        $this->autoconfigurationService = $autoconfigurationService;

        // Register Tabs for backend Structure
        //@suggestion: make this dynamic and to override and add things by hooks
        $this->tabs = [
            "home" => [
                "title" => "Home",
                "identifier" => "home"
            ],
            "autoconfiguration" => [
                "title" => "Autoconfiguration & Reports",
                "identifier" => "autoconfiguration"
            ],
            "settings" => [
                "title" => "Frontend Settings",
                "identifier" => "frontend"
            ],
            "categories" => [
                "title" => "Cookie Categories",
                "identifier" => "categories"
            ],
            "services" => [
                "title" => "Cookie Services",
                "identifier" => "services"
            ]
        ];

    }

    /**
     * Generates the action menu
     */
    protected function initializeModuleTemplate(
        ServerRequestInterface $request
    ): ModuleTemplate {

        $view = $this->moduleTemplateFactory->create($request);

        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('CfCookieModuleMenu');
        $context = '';
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $view->setTitle(
            "Cookie Settings",
            $context
        );


        $view->setFlashMessageQueue($this->getFlashMessageQueue());
        return $view;
    }

    /**
     * Renders the module View
     *
     * @param $moduleTemplate
     * @return ResponseInterface
     */
    public function renderBackendModule($moduleTemplate){
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Executes the static data update wizard in the backend module, which imports the static data from the API, with a simple click.
     *
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException If the database tables are missing.
     */
    public function executeStaticDataUpdateWizard(){
        $service = new StaticDataUpdateWizard(
            $this->cookieServiceRepository,
            $this->cookieCartegoriesRepository,
            $this->cookieFrontendRepository,
            $this->cookieRepository
        );
        return $service->executeUpdate();
    }

    /**
     * Register the language menu in DocHeader
     *
     * @param $moduleTemplate
     * @param $storageUID
     * @return mixed
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function registerLanguageMenu($moduleTemplate,$storageUID){
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $languages =  $this->cookieFrontendRepository->getAllFrontendsFromStorage([$storageUID]);
        $languageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $languageMenu->setIdentifier('languageMenu');
        foreach ($languages as $langauge) {

            if ($this->version->getMajorVersion() < 12) {
                $route = "web_CfCookiemanagerCookiesettings";
            } else {
                $route = "cookiesettings";
            }
            $languageID =    $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;
            $languageUid = (int)$langauge->_getProperty("_languageUid"); //for v12:  (int)$langauge->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID);
            $menuItem = $languageMenu
                ->makeMenuItem()
                ->setTitle( $langauge->getIdentifier())
                ->setHref((string)$uriBuilder->buildUriFromRoute($route, ['id' => $storageUID, 'language' => $languageUid]));
            if (intval($languageID) === $languageUid) {
                $menuItem->setActive(true);
            }
            $languageMenu->addMenuItem($menuItem);
        }

        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        return $moduleTemplate;
    }

    /**
     * Renders the main view for the cookie manager backend module and handles various requests.
     *
     * @return \Psr\Http\Message\ResponseInterface The HTML response.
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException If the database tables are missing.
     */
    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->registerAssets();

        if ($this->request->hasArgument('fileToUpload')) {
            // Retrieve the uploaded preset
            $uploadedFile = $this->request->getArgument('fileToUpload');
            $uploadSuccess = $this->uploadZip($uploadedFile);
            if($uploadSuccess){
                $this->addFlashMessage("File uploaded successfully, now you can configure the cookiemanager offline", "Success", \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
                $this->redirect("index");
            }
        }

        //First installation, the User clicked on Start Configuration after seeing the notice no data in database.
        if(!empty($this->request->getParsedBody()["firstconfigurationinstall"]) &&  $this->request->getParsedBody()["firstconfigurationinstall"] == "start"){
            $status = $this->executeStaticDataUpdateWizard();
            if(!$status){
                $this->addFlashMessage("Error while importing data from API, maybe the endpoint is not reachable", "Error", \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
                $this->view->assign("error_internet",true);
            }else{
                //Successfuly Imported Data from API, now redirect to the same page to show the new data
                header("Refresh:0");
                //$this->redirect("index");
                die();
            }
        }

        if (isset($this->request->getQueryParams()['id']) && !empty((int)$this->request->getQueryParams()['id'])) {
            //Get storage UID based on page ID from the URL parameter
            $storageUID = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::slideField("pages", "uid", (int)$this->request->getQueryParams()['id'], true,true)["uid"];
        }else{
            //No Root page Selected - Show Notice
            $this->view->assignMultiple(['noselection' => true]);
            return $this->renderBackendModule($moduleTemplate);
        }

        //Register Language Menu in DocHeader if there are more than one language
        $moduleTemplate = $this->registerLanguageMenu($moduleTemplate,$storageUID);

        // Check if services are empty or database tables are missing, which indicates a fresh install
        try {
            if (empty($this->cookieServiceRepository->getAllServices($storageUID))) {
                $this->view->assignMultiple(['firstInstall' => true]);
                return $this->renderBackendModule($moduleTemplate);
            }
        } catch (\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException $ex) {
            // Show notice if database tables are missing
            $this->view->assignMultiple(['firstInstall' => true]);
            return $this->renderBackendModule($moduleTemplate);
        }

        /* ====== AutoConfiguration Handling Start ======= */
        $autoConfigurationSetup = [
            "languageID" => $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0,
            "arguments" => $this->request->getArguments(), //POST/GET Forms in Backend Module
        ];

        $newScan = $this->autoconfigurationService->handleAutoConfiguration($storageUID,$autoConfigurationSetup);
        if(!empty($newScan["messages"])){
            //Assign Flash Messages to View
            foreach ($newScan["messages"] as $message){
                $this->addFlashMessage($message[0], $message[1], $message[2]);
            }
        }

        if(!empty($newScan["assignToView"])){
            //Assign Variables to View
            $this->view->assignMultiple($newScan["assignToView"]);
        }
        /* ====== AutoConfiguration Handling End ======= */


        //Fetch Scan Information
        $preparedScans = $this->scansRepository->getScansForStorageAndLanguage([$storageUID],false);
        $languageID =    $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;
        $this->view->assignMultiple(
            [
                'tabs' => $this->tabs,
                'scanTarget' => $this->scansRepository->getTarget($storageUID),
                'storageUID' => $storageUID,
                'scans' => $preparedScans,
                'language' => (int)$languageID,
                'configurationTree' => $this->getConfigurationTree([$storageUID]),
                'extensionConfiguration' =>  GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager')
            ]
        );

        return $this->renderBackendModule($moduleTemplate);
    }

    /**
     * Registers document header buttons.
     *
     * @param ModuleTemplate $moduleTemplate The module template.
     * @return ModuleTemplate Returns the updated module template.
     */
    protected function registerDocHeaderButtons(ModuleTemplate $moduleTemplate): ModuleTemplate
    {
        return $moduleTemplate;
    }

    /**
     * Renders the css and js assets for the backend module.
     *
     * @return void
     */
    public function registerAssets(){
        // Load required CSS & JS modules for the page
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/CookieSettings.css');
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/DataTable.css');
        $this->pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/bootstrap-tour.css');
        $this->pageRenderer->addRequireJsConfiguration(
            [
                "waitSeconds" => 10,
                'paths' => [
                    'jqueryDatatable' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/thirdparty/DataTable.min'),
                    'bootstrapTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/thirdparty/bootstrap-tour'),
                    'initCookieBackend' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/Backend/initCookieBackend'),
                    'TourFunctions' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/TourFunctions'),
                    'TourManager' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/TourManager'),
                    'ServiceTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/ServiceTour'),
                    'FrontendTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/FrontendTour'),
                    'CategoryTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/CategoryTour'),
                ],
                'shim' => [
                    'initCookieBackend' => [ 'deps' => ['jquery', 'jqueryDatatable']],
                    'CategoryTour' => ['deps' => ['initCookieBackend','bootstrap','bootstrapTour']],
                    'jqueryDatatable' => ['exports' => 'jqueryDatatable'],
                ],
            ]
        );

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/CfCookiemanager/TutorialTours/TourManager'); //TODO Refactor to native ECMAScript v6/v11 modules but keep in mind that we currently support TYPO3 v11

    }

    /**
     * Fetches the Configuration Tree of a Language and Storage Page
     *
     * @param array $storageUID
     * @return array
     */
    public function getConfigurationTree($storageUID) : array
    {
        // Prepare data for the configuration tree
        $configurationTree = [];
        $currentLang = false;
        $languageID =    $this->request->getParsedBody()['language'] ?? $this->request->getQueryParams()['language'] ?? 0;
        if(!empty($languageID)){
            $currentLang = $languageID;
        }

        $allCategories = $this->cookieCartegoriesRepository->getAllCategories($storageUID,$currentLang);
        foreach ($allCategories as $category){
            $services = $category->getCookieServices();
            $servicesNew = [];
            foreach ($services as $service){
                $variables = $service->getUnknownVariables();
                if($variables === true){
                    $variables = [];
                }
                $serviceTmp = $service->_getProperties();
                $serviceTmp["localizedUid"] =  $service->_getProperty('_localizedUid');
                $serviceTmp["variablesUnknown"] = array_unique($variables);
                $servicesNew[] = $serviceTmp;
            }


            $configurationTree[$category->getUid()] = [
                "uid" => $category->getUid(),
                "localizedUid" =>  $category->_getProperty('_localizedUid'),
                "category" => $category,
                "countServices" => count($services),
                "services" => $servicesNew
            ];
        }

        return $configurationTree;
    }

    /**
     * Handles the zip file upload, if no internet connection is available on installation. The zip file is extracted and its contents are processed as the external api will do.
     * @param  $fileToUpload
     */
    public function uploadZip($fileToUpload)
    {
        // Define the target directory where the file will be saved
        $targetDirectory = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cf_cookiemanager') . 'Resources/Static/Data/';
        if(!is_dir($targetDirectory)){
            mkdir($targetDirectory);
        }

        // Use the original name of the file to create the target path
        $targetFile = $targetDirectory . basename($fileToUpload['name']);
        if (!move_uploaded_file($fileToUpload['tmp_name'], $targetFile)) {
            die("Failed Upload");
        }

        // File is moved successfully
        // Create a new ZipArchive instance
        $zip = new \ZipArchive();
        // Open the zip file
        if ($zip->open($targetFile) === TRUE) {
            // Iterate over each file in the zip file
            for($i = 0; $i < $zip->numFiles; $i++) {
                // Get the file name
                $fileName = $zip->getNameIndex($i);
                // Check if the file extension is .json
                if(pathinfo($fileName, PATHINFO_EXTENSION) === 'json') {
                    // Extract the file to the target directory
                    $zip->extractTo($targetDirectory, $fileName);
                }
            }

            // Close the zip file
            $zip->close();

            // Remove the zip file
            unlink($targetFile);
        } else {
            die("Failed to open zip file");
        }
        return true;
    }

}