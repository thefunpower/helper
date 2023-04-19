<?php   
/*
    Copyright (c) 2021-2031, All rights reserved.
    This is NOT a freeware
    LICENSE: https://github.com/thefunpower/core/blob/main/LICENSE.md 
    Connect Email: sunkangchina@163.com
*/
namespace helper_v3;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 
class Xls
{
    public static $base_url = 'uploads/tmp/xls/';
    public static $works = [];
    public static $sheet_width = [];
    public static $label = "";
    public static $merge;
    public static $start_line = 1;


    /**
     * 生成XLSX
     * $xls_titles 
     * [
     * 'name' => '产品名称',
     * 'sku_num' => '内部编号',
     * ] 
     * $data = [
     *      ['name'=>'test','sku_num'=>1001]
     * ]
     * $name = '测试'
     * @return string  xls url
     */

    public static function create($xls_titles, $data, $name, $is_output_to_brower = false,$sheet_call = '')
    {
        $base_url   = self::$base_url;
        $save_path  = PATH . $base_url;
        if (!is_dir($save_path)) {
            mkdir($save_path, 0777, true);
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(); 
        if (self::$label) {
            $first = self::$label;
            $sheet->setTitle(self::$label);
            $sheet = $spreadsheet->getSheetByName(self::$label);
            if($sheet_call){
                $sheet_call($sheet,$spreadsheet);
            }
        }
        $options = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
            ],
        ];
        //设置一个worksheet
        self::_sheet($sheet, $xls_titles, $data, $options, self::$sheet_width);
        //追加更多worksheet
        if (self::$works) {
            foreach (self::$works as $vv) {
                $label = $vv['label'];
                $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $label);
                $spreadsheet->addSheet($myWorkSheet);
                $sheet = $spreadsheet->getSheetByName($label);
                self::_sheet($sheet, $vv['title'], $vv['data'], $options, $vv['width']);
            }
        }
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        //合并如 ['A18:E22']
        if (self::$merge && is_array(self::$merge)) {
            foreach (self::$merge as $vv) {
                $spreadsheet->getActiveSheet()->mergeCells($vv);
            }
        }

        $writer = new Xlsx($spreadsheet);
        //向浏览器输入        
        if ($is_output_to_brower) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            return;
        }
        /**
         * 生成产品xls文件
         */
        $writer->save($save_path .  $name . '.xlsx');
        return $base_url . $name . '.xlsx';
    }
    /**
     * 内部使用
     * 处理每行值
     */
    public static function _sheet(&$sheet, $xls_titles, $data, $options, $sheet_width)
    {
        //设置固定宽度
        if ($sheet_width) {
            foreach ($sheet_width as $k => $v) {
                $sheet->getColumnDimension((string)$k)->setWidth((string)$v);
                $sheet->getStyle($k)->applyFromArray($options);
            }
        } else {
            foreach ($sheet->getColumnIterator() as $column) {
                $index =  $column->getColumnIndex();
                $index =  (string)$index;
                $sheet->getColumnDimension($index)->setAutoSize(true);
                $sheet->getStyle($column->getColumnIndex())->applyFromArray($options);
            }
        }
        //从第几行开始
        $start_line = self::$start_line;
        //xls 第一行 
        $k = 1;
        foreach ($xls_titles as $v) {
            $sheet->setCellValue(Xls::getCol($start_line, $k), $v?:' ');
            $k++;
        }
        $i = $start_line+1;
        foreach ($data as $v) {
            $j = 1;
            foreach ($xls_titles as $k1 => $v1) { 
                $sheet->setCellValue(Xls::getCol($i, $j), $v[$k1]?:' ');
                $j++; 
            }
            $i++;
        }
    }
    /**
     * 根据 列、行返回对应xls的key
     * @example 
     * <code>
     * $spreadsheet = new Spreadsheet();
     *   $sheet = $spreadsheet->getActiveSheet();
     *   //xls开始行
     *    $i = 1;
     *    foreach ($array as $k => $v) {
     *        //xls列
     *        $j = 1;
     *        foreach ($v as $v1) {
     *            $sheet->setCellValue(Xls::getCol($i, $j), $v1);
     *            $j++;
     *        }
     *        $i++;
     *    }
     *    $writer = new Xlsx($spreadsheet);
     *    $writer->save($save_path . '/product.xlsx');
     * </code>
     * @param int $start_row 开始行
     * @param int $start_col 开始列
     * @return void
     */
    public static function getCol($start_row, $start_col)
    {
        $array = [];
        /**
         * xls支持最大列数
         */
        $j = 1;
        for ($i = 'A'; $i <= 'Z'; $i++) {
            $array[$j++] = $i;
        }
        /**
         *     第一列  第二列
         * 1   A1      B1
         * 2   A2      B2
         * 3   A3      B3
         * 
         * 传入 2 1 对应 A2
         * 传入 2 2 对应 B2
         */
        return $array[$start_col] . $start_row;
    }
}
