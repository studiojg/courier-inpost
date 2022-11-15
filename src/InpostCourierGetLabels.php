<?php

declare(strict_types=1);

namespace Sylapi\Courier\Inpost;

use Exception;
use GuzzleHttp\Exception\ClientException;
use Sylapi\Courier\Contracts\CourierGetLabels;
use Sylapi\Courier\Contracts\Label as LabelContract;
use Sylapi\Courier\Contracts\LabelFile as LabelFileContract;
use Sylapi\Courier\Entities\Label;
use Sylapi\Courier\Entities\LabelFile;
use Sylapi\Courier\Exceptions\TransportException;
use Sylapi\Courier\Helpers\ResponseHelper;

class InpostCourierGetLabels implements CourierGetLabels
{
    const API_PATH_SINGLE = '/v1/shipments/:shipment_id/label';
    const API_PATH_MULTIPLE = '/v1/organizations/:organization_id/shipments/labels';

    private $session;

    public function __construct(InpostSession $session)
    {
        $this->session = $session;
    }

    public function getLabel(string $shipmentId): LabelContract
    {
        try {
            $stream = $this->session
                ->client()
                ->get(
                    $this->getPathSingle($shipmentId),
                    [
                        'query' => [
                            'type' => $this->session->parameters()->getLabelType(),
                        ],
                    ]
                );

            $result = $stream->getBody()->getContents();

            return new Label((string) $result);
        } catch (ClientException $e) {
            $excaption = new TransportException(InpostResponseErrorHelper::message($e));
            $label = new Label(null);
            ResponseHelper::pushErrorsToResponse($label, [$excaption]);

            return $label;
        } catch (Exception $e) {
            $excaption = new TransportException($e->getMessage(), $e->getCode());
            $label = new Label(null);
            ResponseHelper::pushErrorsToResponse($label, [$excaption]);

            return $label;
        }
    }

    public function getLabelFile(string $shipmentId, string $format = 'pdf') : LabelFileContract
    {
        try {
            $tmpFile = tmpfile();
            $apiPath = strstr($shipmentId, ',') !== false ? $this->getPathMultiple($this->session->parameters()->organization_id) : $this->getPathSingle($shipmentId);
            $params = [
                'type' => $this->session->parameters()->getLabelType(),
                'format' => $format,
            ];
            if (strstr($shipmentId, ',') !== false){
                $params['shipment_ids[]'] = explode(',', $shipmentId);
            }
            $query = \GuzzleHttp\Psr7\Query::build($params);
            $stream = $this->session
                ->client()
                ->get(
                    $apiPath,
                    [
                        'query' => $query,
                        'sink' => $tmpFile
                    ]
                );

            return new LabelFile(stream_get_contents($tmpFile));
        } catch (ClientException $e) {
            $excaption = new TransportException(InpostResponseErrorHelper::message($e));
            $labelFile = new LabelFile(null);
            ResponseHelper::pushErrorsToResponse($labelFile, [$excaption]);

            return $labelFile;
        } catch (Exception $e) {
            $excaption = new TransportException($e->getMessage(), $e->getCode());
            $labelFile = new LabelFile(null);
            ResponseHelper::pushErrorsToResponse($labelFile, [$excaption]);

            return $labelFile;
        }
    }

    private function getPathSingle(string $value)
    {
        return str_replace(':shipment_id', $value, self::API_PATH_SINGLE);
    }

    private function getPathMultiple(string $value)
    {
        return str_replace(':organization_id', $value, self::API_PATH_MULTIPLE);
    }
}
