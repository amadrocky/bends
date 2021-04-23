<?php

namespace App\Service;

use App\Repository\SignaledOffersRepository;
use App\Repository\SignaledDiscussionsRepository;

class ReportsService
{
    public function __construct(SignaledOffersRepository $signaledOffersRepository, SignaledDiscussionsRepository $signaledDiscussionsRepository)
    {
        $this->signaledOffersRepository = $signaledOffersRepository;
        $this->signaledDiscussionsRepository = $signaledDiscussionsRepository;
    }

    /**
     * Count of signaled elements (offers & discussions)
     *
     * @return integer
     */
    public function getCountOfReportedElements(): int
    {
        return count($this->signaledOffersRepository->findByWorkflowState('created')) + count($this->signaledDiscussionsRepository->findByWorkflowState('created'));
    }
}