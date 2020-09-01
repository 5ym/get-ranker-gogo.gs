<?php
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");//メソッド
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//証明書の検証を行わない
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_execの結果を文字列で返す
    $op = ['11', '12', '13', '14'];
    $ot = ['レギュラー' => 0, 'ハイオク' => 1];
    foreach($ot as $otn => $otv) {
        file_put_contents($otn.'.csv', '');
        $f = fopen($otn.'.csv', "w");
        fputcsv($f, ['店舗名', '住所', '順位', '価格', 'リンク', '価格種別', '更新日時', '詳細', '油種', 'ブランド']);
        foreach($op as $value) {
            curl_setopt($curl, CURLOPT_URL, "https://gogo.gs/ranking/".$value."?members%5B0%5D=0&members%5B1%5D=1&submit=1&prefs%5B0%5D=".$value."&span=2&mode=".$otv);
            $dom = new DOMDocument();
            @$dom->loadHTML(curl_exec($curl));
            $xml = simplexml_import_dom($dom);
            foreach(array_map('current', array_chunk($xml->xpath('//tr'), 2)) as $k => $v) {
                // ブランド判別
                $ob = 'その他';
                if(preg_match("/.+_(\d+)_.+/", $v->xpath("//*[@class='maker-icon']")[$k]['src'][0], $matches)) $end=$matches[1];
                switch($end) {
                    case 3:
                        $ob = "ENEOS";
                        break;
                    case 8:
                        $ob = "出光";
                        break;
                    case 7:
                        $ob = "昭和シェル石油";
                        break;
                    case 6:
                        $ob = "コスモ石油";
                        break;
                    case 4:
                        $ob = "KYGNUS";
                        break;
                }                
                // csv一行書き出し
                fputcsv($f, [$v->xpath("//*[@class='shop-name']")[$k],
                    $v->xpath("//*[@class='address']")[$k],
                    $v->xpath("//*[@class='rank']")[$k],
                    $v->xpath("//*[@class='price']")[$k],
                    'https://gogo.gs'.$v->xpath("//*[@class='shop-name']")[$k]["href"][0],
                    $v->xpath("//*[@class='member-text' or @class='normal-text']")[$k],
                    trim($v->xpath("//*[@class='confirm-date']")[$k]),
                    $v->xpath("//*[@class='memo-td']")[$k],
                    $otn,
                    $ob]);
            }
        }
        fclose($f);
    }
    curl_close($curl);