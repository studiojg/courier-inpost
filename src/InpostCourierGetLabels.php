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
    const API_PATH = '/v1/shipments/:shipment_id/label';

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
                    $this->getPath($shipmentId),
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
            $stream = $this->session
                ->client()
                ->get(
                    $this->getPath($shipmentId),
                    [
                        'query' => [
                            'type' => $this->session->parameters()->getLabelType(),
                            'format' => $format,
                        ],
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

    private function getPath(string $value)
    {
        return str_replace(':shipment_id', $value, self::API_PATH);
    }
}
