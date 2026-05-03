<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductGroup;
use App\Models\ProductModel;
use App\Models\ProductType;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $groups = collect([
            'Computers', 'Network Equipment', 'Displays', 'Peripherals', 'Servers',
        ])->mapWithKeys(fn ($name) => [$name => ProductGroup::create(['name' => $name])]);

        $types = collect([
            'Laptop', 'Desktop', 'Switch', 'Router', 'Monitor',
            'Access Point', 'Server', 'Printer', 'UPS', 'NAS',
        ])->mapWithKeys(fn ($name) => [$name => ProductType::create(['name' => $name])]);

        $brandsData = [
            'HP'       => ['ProBook 450 G10', 'EliteBook 840 G9', 'Z2 Tower G9', 'ProLiant DL380'],
            'Dell'     => ['Latitude 5540', 'XPS 15 9530', 'OptiPlex 7010', 'PowerEdge R750'],
            'Apple'    => ['MacBook Pro 14"', 'MacBook Air M2', 'iMac 24"'],
            'Cisco'    => ['Catalyst 9200L', 'Catalyst 9300', 'ASA 5506-X'],
            'Ubiquiti' => ['UniFi AP-AC-Pro', 'USW-Pro-48', 'UDM-Pro'],
            'Lenovo'   => ['ThinkPad X1 Carbon', 'ThinkPad T14', 'ThinkCentre M90q'],
            'Samsung'  => ['27" FHD Monitor', '32" 4K Monitor'],
            'LG'       => ['27UK850 4K', '34WN80C UltraWide'],
            'APC'      => ['Smart-UPS 1500', 'Back-UPS Pro 1500'],
            'Logitech' => ['MX Keys', 'MX Master 3S', 'C920 Webcam'],
        ];

        $brands = [];
        $models = [];

        foreach ($brandsData as $brandName => $modelNames) {
            $brand = ProductBrand::create(['name' => $brandName]);
            $brands[$brandName] = $brand;
            foreach ($modelNames as $modelName) {
                $model = ProductModel::create(['product_brand_id' => $brand->id, 'name' => $modelName]);
                $models["$brandName|$modelName"] = $model;
            }
        }

        $catalog = [
            // Computers — Laptops
            ['HP',      'ProBook 450 G10',      'Computers',         'Laptop',       'HP-PB450G10',   'HP ProBook 450 G10 Laptop'],
            ['HP',      'EliteBook 840 G9',      'Computers',         'Laptop',       'HP-EB840G9',    'HP EliteBook 840 G9 Laptop'],
            ['Dell',    'Latitude 5540',          'Computers',         'Laptop',       'DL-LAT5540',    'Dell Latitude 5540 Laptop'],
            ['Dell',    'XPS 15 9530',            'Computers',         'Laptop',       'DL-XPS1509530', 'Dell XPS 15 9530 Laptop'],
            ['Apple',   'MacBook Pro 14"',        'Computers',         'Laptop',       'AP-MBP14',      'Apple MacBook Pro 14" Laptop'],
            ['Apple',   'MacBook Air M2',         'Computers',         'Laptop',       'AP-MBA-M2',     'Apple MacBook Air M2 Laptop'],
            ['Lenovo',  'ThinkPad X1 Carbon',     'Computers',         'Laptop',       'LN-TPX1C',      'Lenovo ThinkPad X1 Carbon Laptop'],
            ['Lenovo',  'ThinkPad T14',           'Computers',         'Laptop',       'LN-TPT14',      'Lenovo ThinkPad T14 Laptop'],
            // Computers — Desktops
            ['HP',      'Z2 Tower G9',            'Computers',         'Desktop',      'HP-Z2TG9',      'HP Z2 Tower G9 Desktop'],
            ['Dell',    'OptiPlex 7010',           'Computers',         'Desktop',      'DL-OPX7010',    'Dell OptiPlex 7010 Desktop'],
            ['Apple',   'iMac 24"',               'Computers',         'Desktop',      'AP-IMAC24',     'Apple iMac 24" Desktop'],
            ['Lenovo',  'ThinkCentre M90q',        'Computers',         'Desktop',      'LN-TCM90Q',     'Lenovo ThinkCentre M90q Desktop'],
            // Network — Switches
            ['Cisco',   'Catalyst 9200L',          'Network Equipment', 'Switch',       'CS-C9200L',     'Cisco Catalyst 9200L Switch'],
            ['Cisco',   'Catalyst 9300',           'Network Equipment', 'Switch',       'CS-C9300',      'Cisco Catalyst 9300 Switch'],
            ['Ubiquiti','USW-Pro-48',              'Network Equipment', 'Switch',       'UB-USW48',      'Ubiquiti UniFi Switch Pro 48'],
            // Network — Routers & Firewalls
            ['Cisco',   'ASA 5506-X',             'Network Equipment', 'Router',       'CS-ASA5506X',   'Cisco ASA 5506-X Firewall'],
            ['Ubiquiti','UDM-Pro',                'Network Equipment', 'Router',       'UB-UDMP',       'Ubiquiti UDM-Pro Router'],
            // Network — Access Points
            ['Ubiquiti','UniFi AP-AC-Pro',         'Network Equipment', 'Access Point', 'UB-UAPAC-PRO',  'Ubiquiti UniFi AP-AC-Pro'],
            // Displays
            ['Samsung', '27" FHD Monitor',         'Displays',          'Monitor',      'SS-LS27F',      'Samsung 27" FHD Monitor'],
            ['Samsung', '32" 4K Monitor',          'Displays',          'Monitor',      'SS-LS32K',      'Samsung 32" 4K Monitor'],
            ['LG',      '27UK850 4K',              'Displays',          'Monitor',      'LG-27UK850',    'LG 27UK850 4K Monitor'],
            ['LG',      '34WN80C UltraWide',       'Displays',          'Monitor',      'LG-34WN80C',    'LG 34WN80C UltraWide Monitor'],
            // Servers
            ['HP',      'ProLiant DL380',          'Servers',           'Server',       'HP-DL380G10',   'HP ProLiant DL380 Server'],
            ['Dell',    'PowerEdge R750',           'Servers',           'Server',       'DL-PER750',     'Dell PowerEdge R750 Server'],
            // Peripherals
            ['APC',     'Smart-UPS 1500',          'Peripherals',       'UPS',          'APC-SMT1500',   'APC Smart-UPS 1500'],
            ['APC',     'Back-UPS Pro 1500',       'Peripherals',       'UPS',          'APC-BX1500',    'APC Back-UPS Pro 1500'],
            ['Logitech','MX Keys',                 'Peripherals',       'Printer',      'LG-MXKEYS',     'Logitech MX Keys Keyboard'],
            ['Logitech','MX Master 3S',            'Peripherals',       'Printer',      'LG-MXMS3S',     'Logitech MX Master 3S Mouse'],
            ['Logitech','C920 Webcam',             'Peripherals',       'Printer',      'LG-C920',       'Logitech C920 Webcam'],
        ];

        foreach ($catalog as [$brandName, $modelName, $groupName, $typeName, $code, $productName]) {
            Product::create([
                'product_group_id' => $groups[$groupName]->id,
                'product_type_id'  => $types[$typeName]->id,
                'product_brand_id' => $brands[$brandName]->id,
                'product_model_id' => $models["$brandName|$modelName"]->id,
                'code'             => $code,
                'name'             => $productName,
            ]);
        }
    }
}
