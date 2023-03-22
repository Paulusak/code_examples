<?php
declare(strict_types=1);

namespace DataGrid\Settings;

use DataGrid\DataGrid;
use FormManager\AbstractFormData;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Webmozart\Assert\Assert;

final class SavingFormDataIntoSessionStorage
{
    private const SESSION_NAME_PREFIX = 'data_grid_';
    public function __construct(
        private RequestStack $requestStack,
        private SerializerInterface $serializer,
    ) {
    }

    public function store(DataGrid $dataGrid, DataGridSettings $dataGridSettings) : void
    {
        $dataGridSettingsJson = $this->serializer->serialize($dataGridSettings, 'json');

        $session = $this->requestStack->getSession();
        $session->set($this->getStorageKey($dataGrid->name, $dataGrid->getOriginalOptions()), $dataGridSettingsJson);
    }

    // TODO: Check if DB friendly (can it be modified to get rid of another get, maybe nullable settings as param)
    public function applyFilters(DataGrid $dataGrid, ?AbstractFormData $filtersData) : void
    {
        $this->store(
            $dataGrid,
            $this->get($dataGrid)->with(filterData: $filtersData),
        );
    }

    public function changeActiveMode(DataGrid $dataGrid, string $activeModeKey) : void
    {
        $this->store(
            $dataGrid,
            $this->get($dataGrid)->with(activeModeKey: $activeModeKey),
        );
    }

    public function get(DataGrid $dataGrid) : DataGridSettings
    {
        $session = $this->requestStack->getSession();
        $dataGridSettingsJson = $session->get($this->getStorageKey($dataGrid->name, $dataGrid->getOriginalOptions()));

        if ($dataGridSettingsJson === null) {
            return new DataGridSettings();
        }

        /** @var DataGridSettings $dataGridSettings */
        $dataGridSettings = $this->serializer->deserialize($dataGridSettingsJson, DataGridSettings::class, 'json', ['filterClassName' => $dataGrid->filtersForm]);
        Assert::isInstanceOf($dataGridSettings, DataGridSettings::class);
        Assert::nullOrIsInstanceOf($dataGridSettings->filterData, AbstractFormData::class);

        return $dataGridSettings;
    }

    /**
     * @param array<string,scalar> $options
     */
    public function clear(string $dataGridName, array $options) : void
    {
        $session = $this->requestStack->getSession();

        $session->remove($this->getStorageKey($dataGridName, $options));
    }

    /**
     * @param array<string,scalar> $options
     */
    private function getStorageKey(string $dataGridName, array $options) : string
    {
        return \sprintf('%s.%s.%s', self::SESSION_NAME_PREFIX, $dataGridName, \md5(\serialize($options)));
    }
}
