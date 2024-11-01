<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Api {


	/**
     * API calls
     *
     * @var Toret_Manager_Api_Calls

     */
	public Toret_Manager_Api_Calls $request;

    /**
     * Get data
     *
     * @var Toret_Manager_Api_Get
     */
	public Toret_Manager_Api_Get $getData;

    /**
     * Create data
     *
     * @var Toret_Manager_Api_Create
     */
	public Toret_Manager_Api_Create $createData;

    /**
     * Update data
     *
     * @var Toret_Manager_Api_Update
     */
	public Toret_Manager_Api_Update $updateData;

    /**
     * Delete data
     *
     * @var Toret_Manager_Api_Delete
     */
	public Toret_Manager_Api_Delete $deleteData;

	public const TORET_MANAGER_ENDPOINT_LOGIN = 'login.php';

    /**
     * Constructor
     */
	public function __construct() {
		$this->IncludeClasses();
		$this->CreateCall();
	}

    /**
     * Get correct API endpoint based on module and post type
     *
     * @param string $module
     * @param string $action
     * @return string
     */
	public function get_module_endpoint(string $module, string $action ): string
    {
		$enabled_modules = Toret_Manager_Helper_Modules::get_enabled_modules();

		if ( key_exists( $module, $enabled_modules ) ) {
			return $enabled_modules[ $module ] . '/' . $action . '.php';
		}

        if(Toret_Manager_Helper::is_wc_taxonomy($module)){
            $module = 'term';
        }

		// Backup
		return TORET_MANAGER_API_ENDPOINTS[ $module ] . '/' . $action . '.php';
	}

	/**
	 * Load API libraries
	 */
	private function IncludeClasses() {
		include_once( 'class-toret-manager-api-calls.php' );
		include_once( 'class-toret-manager-api-get.php' );
		include_once( 'class-toret-manager-api-create.php' );
		include_once( 'class-toret-manager-api-delete.php' );
		include_once( 'class-toret-manager-api-update.php' );
	}

	/**
	 * Create calls
	 */
	private function CreateCall() {
		$this->request    = new Toret_Manager_Api_Calls();
		$this->getData    = new Toret_Manager_Api_Get();
		$this->createData = new Toret_Manager_Api_Create();
		$this->deleteData = new Toret_Manager_Api_Delete();
		$this->updateData = new Toret_Manager_Api_Update();
	}


}

/**
 * Get class instance
 *
 * @return Toret_Manager_Api
 */
function ToretManagerApi(): Toret_Manager_Api {
	return new Toret_Manager_Api();
}
