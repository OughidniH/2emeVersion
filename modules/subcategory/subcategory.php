<?php

// Exit if accessed directly or outside of PrestaShop context
if (!defined('_PS_VERSION_')) {
    exit;
}

// Define the Subcategory module class
class Subcategory extends Module
{
    // Module constructor: set basic module information
    public function __construct()
    {
        $this->name = 'subcategory';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Perpetualcode';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SubCategory');
        $this->description = $this->l('Displays subcategory name of a product.');

        // Define compatibility with PrestaShop versions
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    // Handle module installation
    public function install()
    {
        return parent::install()
            // Register custom hook to display subcategory
            && $this->registerHook('displayProductSubCategory');
    }

    // Handle module uninstallation and remove configuration settings
    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('SubCategory_ENABLED')
            && Configuration::deleteByName('SubCategory_CATEGORY_NAME');
    }

    // Render the configuration page content
    public function getContent()
    {
        // Check if the form has been submitted
        if (Tools::isSubmit('submitSubcategoryModule')) {
            $this->postProcess();
        }

        return $this->renderForm();
    }

    // Process form submission and save configuration values
    protected function postProcess()
    {
        Configuration::updateValue('SubCategory_ENABLED', Tools::getValue('SubCategory_ENABLED'));
        Configuration::updateValue('SubCategory_CATEGORY_NAME', Tools::getValue('SubCategory_CATEGORY_NAME'));
    }

    // Retrieve current configuration values to populate the form
    protected function getConfigFormValues()
    {
        return array(
            'SubCategory_ENABLED' => Configuration::get('SubCategory_ENABLED'),
            'SubCategory_CATEGORY_NAME' => Configuration::get('SubCategory_CATEGORY_NAME'),
        );
    }

    // Render the configuration form in the back office
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSubcategoryModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Assign values to be used in the template
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    // Define the configuration form structure
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable feature'),
                        'name' => 'SubCategory_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->l('Enable or disable this functionality.'),
                        'values' => array(
                            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Enabled')),
                            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('Disabled')),
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Category Name'),
                        'name' => 'SubCategory_CATEGORY_NAME',
                        'desc' => $this->l('Enter a category name to display.'),
                        'required' => false,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    // Custom hook method: Displays the deepest subcategory of a product
    public function hookDisplayProductSubCategory($params)
    {
        // Validate product ID presence
        if (!isset($params['product']) || empty($params['product']['id_product'])) {
            return '';
        }

        $id_product = (int)$params['product']['id_product'];

        // Load product and get its associated categories
        $product = new Product($id_product, false, $this->context->language->id);
        $categories = $product->getCategories();

        $sub_category = '';
        $max_depth = -1;

        // Loop through categories and select the one with the highest depth
        foreach ($categories as $id_category) {
            $cat = new Category($id_category, $this->context->language->id);
            if ($cat->level_depth > $max_depth) {
                $max_depth = $cat->level_depth;
                $sub_category = $cat->name;
            }
        }

        // Assign the selected subcategory name to the template
        $this->context->smarty->assign([
            'sub_category' => $sub_category,
        ]);

        // Render the custom template for subcategory display
        return $this->display(__FILE__, 'views/templates/displayProductSubCategory.tpl');
    }
}
