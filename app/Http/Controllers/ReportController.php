<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use JasperPHP;
use PHPJasper\PHPJasper;

class ReportController extends Controller
{
    //
    // public function getDatabaseConfigMysql()
    // {
    //     return [
    //         'driver'   => 'mysql',
    //         'host'     => env('DB_HOST'),
    //         'port'     => env('DB_PORT'),
    //         'username' => env('DB_USERNAME'),
    //         'password' => env('DB_PASSWORD'),
    //         'database' => env('DB_DATABASE')
    //     ];
    // }
    // public function generateReport()
    // {

    //  $extension = 'pdf' ;
    //  $name = 'AHLC_BD';
    //  $filename =  $name  . time();
    //  $output = base_path('/public/reports/' . $filename);

    //  JasperPHP::compile(storage_path('app/public'). '/reports/AHLC_BD.jrxml')->execute();

    //  JasperPHP::process(
    //    storage_path('app/public/reports/AHLC_BD.jasper') ,
    //    $output,
    //    array($extension),
    //    array('user_name' => ''),
    //    $this->getDatabaseConfigMysql(),
    //    "pt_BR"
    //  )->execute();

    //  /* verificando possiveis erros - Try to output the command using the function output();
    //  Comente o comando acima e descomente o que esta abaixo, pegue o rerultado e execute no termnial
    //  para verificar o erro */

    // /* echo JasperPHP::process(
    //    storage_path('app/public/relatorios/reportJasper.jasper') ,
    //    $output,
    //    array($extension),
    //    array('user_name' => ''),
    //    $this->getDatabaseConfigMysql(),
    //    "pt_BR"
    //  )->output();
    //   exit(); */

    // $file = $output .'.'.$extension ;

    // if (!file_exists($file)) {
    //   abort(404);
    // }
    // if($extension == 'xls')
    //  {
    //    header('Content-Description: File Excel');
    //    header('Content-Type: application/x-msexcel');
    //    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    //    header('Expires: 0');
    //    header('Cache-Control: must-revalidate');
    //    header('Pragma: public');
    //    header('Content-Length: ' . filesize($file));
    //    flush(); // Flush system output buffer
    //    readfile($file);
    //    unlink($file) ;
    //    die();
    //  }
    //  else if ($extension == 'pdf')
    //   {
    //     return response()->file($file)->deleteFileAfterSend();
    //   }

    // }

    public function generateReport(){
      $input = public_path('reports/AHLC_BD.jasper');
      $output = public_path('reports');

      $options = [
          'format' => ['pdf'],
          'params' => [],
          'db_connection' => [
              'driver' => 'mysql',
              'host' => 'localhost',
              'port' => '3306',
              'username' => 'pletter',
              'password' => 'pletter',
              'database' => 'pletterdb',
          ],
      ];

      $jasper = new PHPJasper;

      $jasper->process(
          $input,
          $output,
          $options
      )->execute();
      }
}
