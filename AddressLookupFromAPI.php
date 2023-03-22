<?php
declare(strict_types=1);

namespace Foxentry;

use Foxentry\Form\AddressSelectData;
use Foxentry\Form\AddressSuggestionData;
use Symfony\Component\Serializer\SerializerInterface;
use Webmozart\Assert\Assert;

class AddressLookupFromAPI
{
    public const PROVIDER_CODE = 'foxentry';
    public const ENDPOINT = 'locations/points/search';

    public function __construct(
        private SerializerInterface $serializer,
        private FoxentryClient $foxentryClient
    ) {
    }

    public function getAddressPointById(int $id) : FoxentryAddressPoint
    {
        $foxentryRequest = new FoxentryRequest();
        $foxentryRequest->addSearchQueryParam(
            [FoxentryRequest::SEARCH_MODE_MATCH],
            FoxentryRequest::SEARCH_KEY_ID,
            $id
        );

        $foxentryResults = $this->getFoxentryAddressPointsResults($foxentryRequest);

        // As you got suggestions from previous requests, there is currently no possibility of not having result
        Assert::notEmpty($foxentryResults);

        return $foxentryResults[0];
    }

    /**
     * @return array<int, FoxentryAddressPoint>
     */
    public function getFoxentryAddressPointsResults(FoxentryRequest $foxentryRequest) : array
    {
        $results = [];
        $plainResults = ($this->foxentryClient)($foxentryRequest, self::ENDPOINT);
        foreach ($plainResults as $plainResult) {
            $json = \json_encode($plainResult);
            /** @var FoxentryAddressPoint $deserializedItem */
            $deserializedItem = $this->serializer->deserialize($json, FoxentryAddressPoint::class, 'json');
            $results[] = $deserializedItem;
        }

        return $results;
    }

    /**
     * @param array<int,FoxentryAddressPoint> $results
     *
     * @return array<int, AddressSelectData>
     */
    public function getAddressSelectDataFromFoxentryAddressPoints(array $results) : array
    {
        return \array_map(
            fn (FoxentryAddressPoint $addressPoint) => new AddressSelectData(
                self::PROVIDER_CODE . AddressSuggestionData::PROVIDER_SEPARATOR_SIGN . $addressPoint->id,
                $addressPoint->streetWithNumber . ' (' . $addressPoint->city['name'] . ')'
            ), $results
        );
    }
}
