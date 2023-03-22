<?php
declare(strict_types=1);

namespace Common\Calendar\Application\Controller;

use Bridge\Symfony\Framework\AbstractController;
use DataGrid\GetDataGridWithName;
use DataGrid\Results\GetResultsFromRequest;
use DataGrid\View\CreateDataGridView;
use Rent\Domain\Portfolio\GetActivePortfolio;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Webmozart\Assert\Assert;

final class ControllerAction extends AbstractController
{
    public function __construct(
        private GetDataGridWithName $getDataGridWithName,
        private GetResultsFromRequest $getResults,
        private CreateDataGridView $createDataGridView,
        private GetActivePortfolio $getActivePortfolio
    ) {
    }

    // when you want to access the calendar on separate page, while on dashboard, calendar component is called
    #[Route(path: '/calendar', name: 'calendar')]
    public function __invoke(Request $request) : Response
    {
        return $this->render('@SworpCommonCalendar/calendar_page.html.twig', [
            'portfolioId' => $this->getActivePortfolio->id(),
        ]);
    }

    #[Route(path: '/calendar-events', name: 'calendar_events')]
    public function handleCalendarEvents(Request $request) : Response
    {
        $dataGridName = $request->get('dataGridName');
        $dataGridOptions = $request->get('options', []);

        Assert::string($dataGridName);
        Assert::isArray($dataGridOptions);
        Assert::allScalar($dataGridOptions);

        $dataGrid = ($this->getDataGridWithName)($dataGridName, $dataGridOptions);
        $results = ($this->getResults)($dataGrid, $request);
        $dataGrid->setResults($results);

        return $this->render('@CommonCalendar/data_grid/calendar_events.html.twig', [
            'data_grid' => ($this->createDataGridView)($dataGrid),
        ]);
    }
}
