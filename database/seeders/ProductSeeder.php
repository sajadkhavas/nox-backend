<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // برندها
        $brands = [
            ['name' => 'Parker Hannifin', 'slug' => 'parker-hannifin', 'country' => 'US', 'is_active' => true],
            ['name' => 'Peak Scientific', 'slug' => 'peak-scientific', 'country' => 'UK', 'is_active' => true],
            ['name' => 'Edwards', 'slug' => 'edwards', 'country' => 'UK', 'is_active' => true],
            ['name' => 'Watson-Marlow', 'slug' => 'watson-marlow', 'country' => 'UK', 'is_active' => true],
            ['name' => 'KNF', 'slug' => 'knf', 'country' => 'DE', 'is_active' => true],
            ['name' => 'Dräger', 'slug' => 'drager', 'country' => 'DE', 'is_active' => true],
            ['name' => 'MSA Safety', 'slug' => 'msa-safety', 'country' => 'US', 'is_active' => true],
            ['name' => 'Endress+Hauser', 'slug' => 'endress-hauser', 'country' => 'CH', 'is_active' => true],
            ['name' => 'Bronkhorst', 'slug' => 'bronkhorst', 'country' => 'NL', 'is_active' => true],
            ['name' => 'Brooks Instrument', 'slug' => 'brooks-instrument', 'country' => 'US', 'is_active' => true],
            ['name' => 'SICK', 'slug' => 'sick', 'country' => 'DE', 'is_active' => true],
            ['name' => 'Rockwell Automation', 'slug' => 'rockwell-automation', 'country' => 'US', 'is_active' => true],
            ['name' => 'Schneider Electric', 'slug' => 'schneider-electric', 'country' => 'FR', 'is_active' => true],
            ['name' => 'Emerson', 'slug' => 'emerson', 'country' => 'US', 'is_active' => true],
            ['name' => 'ABB', 'slug' => 'abb', 'country' => 'SE', 'is_active' => true],
            ['name' => 'Yokogawa', 'slug' => 'yokogawa', 'country' => 'JP', 'is_active' => true],
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->updateOrInsert(
                ['slug' => $brand['slug']],
                array_merge($brand, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $brandMap = DB::table('brands')->pluck('id', 'slug');
        $categoryMap = DB::table('categories')->pluck('id', 'slug');
        $subcategoryMap = DB::table('subcategories')->pluck('id', 'slug');

        $products = [
            ['slug'=>'ng-500','name'=>'ژنراتور نیتروژن NG-500','model'=>'NG-500','brand_slug'=>'parker-hannifin','category_slug'=>'gas-generators','subcategory_slug'=>'nitrogen-gen','country'=>'US','price_range'=>'mid','in_stock'=>true,'description'=>'ژنراتور نیتروژن با خلوص بالا برای کاربردهای آزمایشگاهی و صنعتی.','specs'=>['purity'=>'99.999%','flowRate'=>'0-500 mL/min','pressure'=>'0-100 psi'],'usage'=>['research','industrial'],'applications'=>['GC-MS','LCMS','آنالیز عنصری']],
            ['slug'=>'ng-1000','name'=>'ژنراتور نیتروژن صنعتی NG-1000','model'=>'NG-1000','brand_slug'=>'parker-hannifin','category_slug'=>'gas-generators','subcategory_slug'=>'nitrogen-gen','country'=>'US','price_range'=>'premium','in_stock'=>true,'description'=>'ژنراتور نیتروژن صنعتی با ظرفیت بالا و عملکرد مداوم ۲۴/۷.','specs'=>['purity'=>'99.99%','flowRate'=>'0-1000 mL/min','pressure'=>'0-150 psi'],'usage'=>['industrial'],'applications'=>['صنایع غذایی','الکترونیک','متالورژی']],
            ['slug'=>'hg-300','name'=>'ژنراتور هیدروژن HG-300','model'=>'HG-300','brand_slug'=>'peak-scientific','category_slug'=>'gas-generators','subcategory_slug'=>'hydrogen-gen','country'=>'UK','price_range'=>'mid','in_stock'=>true,'description'=>'ژنراتور هیدروژن ایمن با فناوری PEM برای دستگاه‌های GC.','specs'=>['purity'=>'99.9999%','flowRate'=>'0-300 mL/min','pressure'=>'0-100 psi'],'usage'=>['research','educational'],'applications'=>['GC','FID','سوخت سلولی']],
            ['slug'=>'genius-xe35','name'=>'ژنراتور نیتروژن Genius XE 35','model'=>'Genius XE 35','brand_slug'=>'peak-scientific','category_slug'=>'gas-generators','subcategory_slug'=>'nitrogen-gen','country'=>'UK','price_range'=>'premium','in_stock'=>true,'description'=>'ژنراتور نیتروژن آزمایشگاهی Peak Scientific برای طیف‌سنج‌های جرمی LC-MS.','specs'=>['purity'=>'99.5%','flowRate'=>'0-35 L/min','pressure'=>'0-116 psi'],'usage'=>['research'],'applications'=>['LC-MS','UHPLC-MS','آنالیز دارویی']],
            ['slug'=>'ag-200','name'=>'ژنراتور هوای خشک AG-200','model'=>'AG-200','brand_slug'=>'peak-scientific','category_slug'=>'gas-generators','subcategory_slug'=>'dry-air-gen','country'=>'UK','price_range'=>'mid','in_stock'=>false,'description'=>'ژنراتور هوای خشک بدون روغن با نقطه شبنم پایین.','specs'=>['flowRate'=>'0-200 L/min','pressure'=>'0-120 psi'],'usage'=>['research','industrial'],'applications'=>['FTIR','TOC','آنالیز رطوبت']],
            ['slug'=>'vp-100','name'=>'پمپ خلاء روتاری VP-100','model'=>'VP-100','brand_slug'=>'edwards','category_slug'=>'lab-pumps','subcategory_slug'=>'vacuum-pump','country'=>'UK','price_range'=>'mid','in_stock'=>true,'description'=>'پمپ خلاء روتاری دو مرحله‌ای با عملکرد بالا و صدای کم.','specs'=>['flowRate'=>'100 L/min','pressure'=>'10⁻³ mbar','voltage'=>'220V / 50Hz'],'usage'=>['research','industrial'],'applications'=>['تقطیر خلاء','خشک‌سازی','فیلتراسیون']],
            ['slug'=>'pp-50','name'=>'پمپ پریستالتیک PP-50','model'=>'PP-50','brand_slug'=>'watson-marlow','category_slug'=>'lab-pumps','subcategory_slug'=>'peristaltic-pump','country'=>'UK','price_range'=>'premium','in_stock'=>true,'description'=>'پمپ پریستالتیک دیجیتال با کنترل دقیق دبی.','specs'=>['flowRate'=>'0.1-3400 mL/min','pressure'=>'0-3 bar','voltage'=>'110-240V'],'usage'=>['research','industrial'],'applications'=>['انتقال سیال','دوز‌بندی','بیوتکنولوژی']],
            ['slug'=>'n840-knf','name'=>'پمپ دیافراگمی LABOPORT N 840','model'=>'N 840.3 FT.18','brand_slug'=>'knf','category_slug'=>'lab-pumps','subcategory_slug'=>'diaphragm-pump','country'=>'DE','price_range'=>'budget','in_stock'=>true,'description'=>'پمپ دیافراگمی بدون روغن KNF ساخت آلمان.','specs'=>['flowRate'=>'34 L/min','voltage'=>'220V / 50Hz'],'usage'=>['research','educational'],'applications'=>['انتقال گاز','نمونه‌برداری','شیمی']],
            ['slug'=>'gd-4x','name'=>'دتکتور چند گازی پرتابل GD-4X','model'=>'GD-4X','brand_slug'=>'drager','category_slug'=>'gas-detectors','subcategory_slug'=>'multi-gas','country'=>'DE','price_range'=>'mid','in_stock'=>true,'description'=>'دتکتور پرتابل ۴ گازی با تاییدیه ATEX.','specs'=>['gasType'=>'O₂, LEL, CO, H₂S','certification'=>'ATEX Zone 0'],'usage'=>['industrial'],'applications'=>['ایمنی صنعتی','پتروشیمی','معادن']],
            ['slug'=>'xam5600','name'=>'دتکتور چندگاز X-am 5600','model'=>'X-am 5600','brand_slug'=>'drager','category_slug'=>'gas-detectors','subcategory_slug'=>'multi-gas','country'=>'DE','price_range'=>'premium','in_stock'=>true,'description'=>'دتکتور چندگاز حرفه‌ای با قابلیت اندازه‌گیری تا ۶ گاز.','specs'=>['gasType'=>'O₂, LEL, CO, H₂S, NO₂, SO₂','certification'=>'ATEX, IECEx'],'usage'=>['industrial'],'applications'=>['پتروشیمی','معادن','آتش‌نشانی']],
            ['slug'=>'gd-tox','name'=>'دتکتور گاز سمی GD-TOX','model'=>'GD-TOX','brand_slug'=>'msa-safety','category_slug'=>'gas-detectors','subcategory_slug'=>'toxic-detector','country'=>'US','price_range'=>'mid','in_stock'=>false,'description'=>'دتکتور گاز سمی الکتروشیمیایی با حساسیت بالا.','specs'=>['gasType'=>'CO, NO₂, Cl₂, NH₃','certification'=>'ATEX, UL'],'usage'=>['industrial','research'],'applications'=>['آزمایشگاه شیمی','بیمارستان','صنایع شیمیایی']],
            ['slug'=>'promag-w400','name'=>'فلومتر الکترومغناطیسی Promag W 400','model'=>'Promag W 400','brand_slug'=>'endress-hauser','category_slug'=>'flow-meters','subcategory_slug'=>'electromagnetic-flow','country'=>'CH','price_range'=>'premium','in_stock'=>true,'description'=>'فلومتر الکترومغناطیسی با دقت بالای ±۰.۲٪.','specs'=>['range'=>'DN10 تا DN2000','accuracy'=>'±0.2%','protocol'=>'HART / Modbus'],'usage'=>['industrial'],'applications'=>['آب و فاضلاب','شیمیایی','دارویی']],
            ['slug'=>'fc-100','name'=>'فلوکنترلر جرمی MFC-100','model'=>'MFC-100','brand_slug'=>'bronkhorst','category_slug'=>'flow-meters','subcategory_slug'=>'mass-flow-controller','country'=>'NL','price_range'=>'premium','in_stock'=>true,'description'=>'فلوکنترلر جرمی حرارتی با کنترل دقیق گاز.','specs'=>['range'=>'0.1 sccm تا 50 slm','accuracy'=>'±0.5%','protocol'=>'RS-485 / Modbus'],'usage'=>['research','industrial'],'applications'=>['CVD','اسپاترینگ','نیمه‌هادی']],
            ['slug'=>'gf80-brooks','name'=>'فلوکنترلر جرمی GF80','model'=>'GF80','brand_slug'=>'brooks-instrument','category_slug'=>'flow-meters','subcategory_slug'=>'mass-flow-controller','country'=>'US','price_range'=>'premium','in_stock'=>true,'description'=>'فلوکنترلر جرمی با دقت ±۰.۵٪.','specs'=>['range'=>'0.2 sccm تا 100 slm','accuracy'=>'±0.5%','protocol'=>'EtherCAT / DeviceNet'],'usage'=>['research','industrial'],'applications'=>['نیمه‌هادی','CVD','اچینگ']],
            ['slug'=>'fm-ul','name'=>'فلومتر اولتراسونیک FM-UL','model'=>'FM-UL','brand_slug'=>'sick','category_slug'=>'flow-meters','subcategory_slug'=>'ultrasonic-flow','country'=>'DE','price_range'=>'mid','in_stock'=>true,'description'=>'فلومتر اولتراسونیک غیرتماسی.','specs'=>['range'=>'DN25 تا DN3000','accuracy'=>'±1%','protocol'=>'Modbus / HART'],'usage'=>['industrial'],'applications'=>['گاز طبیعی','بخار','هوای فشرده']],
            ['slug'=>'s7-1500f','name'=>'PLC SIMATIC S7-1500F','model'=>'CPU 1513F-1 PN','brand_slug'=>'siemens','category_slug'=>'plc-equipment','subcategory_slug'=>'plc-cpu','country'=>'DE','price_range'=>'premium','in_stock'=>true,'description'=>'پردازنده PLC سری S7-1500F زیمنس با Safety Integrated.','specs'=>['ioCount'=>'حداکثر 65536 نقطه','protocol'=>'PROFINET / OPC UA','certification'=>'SIL 3'],'usage'=>['industrial'],'applications'=>['اتوماسیون','خطوط تولید','SCADA']],
            ['slug'=>'compactlogix-5380','name'=>'PLC CompactLogix 5380','model'=>'5069-L310ER','brand_slug'=>'rockwell-automation','category_slug'=>'plc-equipment','subcategory_slug'=>'plc-cpu','country'=>'US','price_range'=>'premium','in_stock'=>true,'description'=>'PLC فشرده Rockwell Automation مدل CompactLogix 5380.','specs'=>['ioCount'=>'حداکثر 120000 نقطه','protocol'=>'EtherNet/IP / CIP Safety'],'usage'=>['industrial'],'applications'=>['خطوط تولید','بسته‌بندی','رباتیک']],
            ['slug'=>'modicon-m580','name'=>'PLC Modicon M580','model'=>'BMEP584040','brand_slug'=>'schneider-electric','category_slug'=>'plc-equipment','subcategory_slug'=>'plc-cpu','country'=>'FR','price_range'=>'premium','in_stock'=>true,'description'=>'اولین ePAC جهان با Ethernet یکپارچه از Schneider Electric.','specs'=>['ioCount'=>'حداکثر 128000 نقطه','protocol'=>'Ethernet / Modbus TCP','certification'=>'Achilles Level 2'],'usage'=>['industrial'],'applications'=>['نیروگاه','آب و فاضلاب','نفت و گاز']],
            ['slug'=>'plc-io-sm','name'=>'ماژول ورودی/خروجی SM-1231','model'=>'SM-1231','brand_slug'=>'siemens','category_slug'=>'plc-equipment','subcategory_slug'=>'plc-io','country'=>'DE','price_range'=>'budget','in_stock'=>true,'description'=>'ماژول ورودی آنالوگ ۸ کاناله با رزولوشن ۱۶ بیت.','specs'=>['ioCount'=>'8 AI / 16-bit','range'=>'±10V / 0-20mA'],'usage'=>['industrial','educational'],'applications'=>['اندازه‌گیری آنالوگ','مانیتورینگ']],
            ['slug'=>'hmi-10','name'=>'پنل HMI ده اینچ TP-1000','model'=>'TP-1000','brand_slug'=>'siemens','category_slug'=>'plc-equipment','subcategory_slug'=>'hmi-panel','country'=>'DE','price_range'=>'mid','in_stock'=>true,'description'=>'پنل لمسی HMI ده اینچ با نمایشگر TFT.','specs'=>['resolution'=>'1024×600 پیکسل','protocol'=>'PROFINET / Ethernet'],'usage'=>['industrial','educational'],'applications'=>['واسط اپراتور','کنترل فرآیند']],
            ['slug'=>'deltav-emerson','name'=>'سیستم کنترل DeltaV S-Series','model'=>'DeltaV S-Series','brand_slug'=>'emerson','category_slug'=>'plc-equipment','subcategory_slug'=>'plc-cpu','country'=>'US','price_range'=>'premium','in_stock'=>true,'description'=>'سیستم کنترل توزیع‌شده Emerson DeltaV S-Series.','specs'=>['protocol'=>'HART / Foundation Fieldbus','certification'=>'SIL 3'],'usage'=>['industrial'],'applications'=>['پالایشگاه','پتروشیمی','داروسازی']],
            ['slug'=>'acs580-abb','name'=>'درایو فرکانس متغیر ACS580','model'=>'ACS580-01','brand_slug'=>'abb','category_slug'=>'plc-equipment','subcategory_slug'=>'plc-io','country'=>'SE','price_range'=>'mid','in_stock'=>true,'description'=>'درایو فرکانس متغیر ABB با توان ۰.۷۵ تا ۵۰۰ کیلووات.','specs'=>['voltage'=>'380-480V 3AC','protocol'=>'Modbus RTU / PROFINET'],'usage'=>['industrial'],'applications'=>['پمپ‌ها','فن‌ها','کمپرسورها']],
            ['slug'=>'centum-vp-yokogawa','name'=>'سیستم CENTUM VP R6','model'=>'CENTUM VP R6','brand_slug'=>'yokogawa','category_slug'=>'plc-equipment','subcategory_slug'=>'plc-cpu','country'=>'JP','price_range'=>'premium','in_stock'=>true,'description'=>'سیستم کنترل توزیع‌شده Yokogawa CENTUM VP R6.','specs'=>['ioCount'=>'حداکثر 40000 نقطه','protocol'=>'FOUNDATION Fieldbus / HART','certification'=>'SIL 3'],'usage'=>['industrial'],'applications'=>['پالایشگاه','LNG','نیروگاه']],
        ];

        foreach ($products as $p) {
            $brandId = $brandMap[$p['brand_slug']] ?? null;
            $categoryId = $categoryMap[$p['category_slug']] ?? null;
            $subcategoryId = $subcategoryMap[$p['subcategory_slug']] ?? null;

            DB::table('products')->updateOrInsert(
                ['slug' => $p['slug']],
                [
                    'name' => $p['name'],
                    'slug' => $p['slug'],
                    'model' => $p['model'],
                    'brand_id' => $brandId,
                    'category_id' => $categoryId,
                    'subcategory_id' => $subcategoryId,
                    'country' => $p['country'],
                    'price_range' => $p['price_range'],
                    'in_stock' => $p['in_stock'],
                    'description' => $p['description'],
                    'specs' => json_encode($p['specs']),
                    'usage' => json_encode($p['usage']),
                    'applications' => json_encode($p['applications']),
                    'status' => 'published',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ ' . count($products) . ' محصول با موفقیت وارد شد!');
    }
}
