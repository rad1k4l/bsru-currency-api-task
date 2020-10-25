<?php


namespace app\commands;

use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\httpclient\Client;

class UpdateCurrencyController extends Controller
{

    private $currencyEndpoint = "http://www.cbr.ru/scripts/XML_daily.asp";

    /**
     * @return int
     */
    public function actionIndex(): int
    {
        echo "[X]\tloading content...";
        try {
            $content = $this->getContent();
            echo "[OK]\n";
        } catch (Exception $exception) {
            echo "[FAIL]\n";
            echo "Msg: {$exception->getMessage()}\n";
            return ExitCode::IOERR;
        }


        echo "[X]\tParsing XML...";
        try {
            $currencies = $this->parse($content);
            echo "[OK]\n";
        } catch (Exception $exception) {
            echo "[FAIL]\n";
            echo "Msg: {$exception->getMessage()}\n";
            return ExitCode::IOERR;
        }


        echo "[X]\tUpdating database...";
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($currencies as $currency) {
                Yii::$app->db->createCommand()->upsert('currency', $currency)->execute();
            }
            $transaction->commit();
            echo "[OK]\n";
        } catch (Exception $e) {
            $transaction->rollBack();
            echo "[FAIL]\nError: {$e->getMessage()}";
            return ExitCode::IOERR;
        }

        return ExitCode::OK;
    }

    /**
     * @return string|null
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception|Exception
     */
    public function getContent(): ?string
    {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod("GET")
            ->setUrl($this->currencyEndpoint)->send();

        if ($response->isOk) {
            return $response->getContent();
        }
        throw new Exception("Non Ok({$response->statusCode}) response from currency endpoint");
    }

    /**
     * @param string $rawXml
     * @return array
     * @throws Exception
     */
    public function parse(string $rawXml): array
    {
        $xml = simplexml_load_string($rawXml);
        if (false === $xml) throw new Exception("Xml parse error");

        $result = [];

        foreach ($xml->Valute as $item) {
            $result[] = [
                'name' => (string)$item->Name,
                'code' => intval($item->NumCode),
                'rate' => floatval(str_replace(',', '.', (string)$item->Value))
            ];
        }

        return $result;
    }


}