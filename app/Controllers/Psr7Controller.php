<?php

namespace App\Controllers;

use Psr\Http\Message\UploadedFileInterface;
use Swoft\Bean\Annotation\AutoController;
use Swoft\Bean\Annotation\RequestMapping;
use Swoft\Web\Controller;

/**
 * @AutoController(prefix="/psr7")
 * @uses      Psr7Controller
 * @version   2017-11-05
 * @author    huangzhhui <huangzhwork@gmail.com>
 * @copyright Copyright 2010-2017 Swoft software
 * @license   PHP Version 7.x {@link http://www.php.net/license/3_0.txt}
 */
class Psr7Controller extends Controller
{

    /**
     * @RequestMapping()
     */
    public function actionGet()
    {
        $param1 = $this->request()->query('param1');
        $param2 = $this->request()->query('param2', 'defaultValue');
        return compact('param1', 'param2');
    }

    /**
     * @RequestMapping()
     */
    public function actionPost()
    {
        $param1 = $this->request()->post('param1');
        $param2 = $this->request()->post('param2');
        return $this->outputJson(compact('param1', 'param2'));
    }

    /**
     * @RequestMapping()
     */
    public function actionInput()
    {
        $param1 = $this->request()->input('param1');
        $param2 = $this->request()->input();
        return $this->outputJson(compact('param1', 'param2'));
    }

    /**
     * @RequestMapping()
     */
    public function actionRaw()
    {
        $param1 = $this->request()->raw();
        return $this->outputJson(compact('param1'));
    }

    /**
     * @RequestMapping()
     * @return \Swoft\Web\Response
     */
    public function actionCookies()
    {
        $cookie1 = $this->request()->cookie();
        return $this->outputJson(compact('cookie1'));
    }

    /**
     * @RequestMapping()
     * @return \Swoft\Web\Response
     */
    public function actionHeader()
    {
        $header1 = $this->request()->header();
        $header2 = $this->request()->header('host');
        return $this->outputJson(compact('header1', 'header2'));
    }

    /**
     * @RequestMapping()
     * @return \Swoft\Web\Response
     */
    public function actionJson()
    {
        $json = $this->request()->json();
        $jsonParam = $this->request()->json('jsonParam');
        return $this->outputJson(compact('json', 'jsonParam'));
    }

    /**
     * @RequestMapping()
     * @return \Swoft\Web\Response
     */
    public function actionFiles()
    {
        $files = $this->request()->file();
        foreach ($files as $file) {
            if ($file instanceof UploadedFileInterface) {
                try {
                    $file->moveTo('@runtime/uploadfiles/1.png');
                    $move = true;
                } catch (\Throwable $e) {
                    $move = false;
                }
            }
        }

        return $this->outputJson(compact('move'));
    }

}