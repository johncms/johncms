<?php

declare(strict_types=1);

namespace Johncms\Checker;

use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckReportDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Throwable;

class SystemChecker implements SystemCheckerInterface
{
    /**
     * A locator of tagged checks only, not the application container. Reading its class keys does
     * not instantiate services: the dashboard and installer never construct page-only checks,
     * and a failed dependency can be reported just like a failed run.
     */
    public function __construct(
        private ServiceProviderInterface $checks,
        private LoggerInterface $logger,
    ) {
    }

    public function run(CheckProfile $profile = CheckProfile::Page): CheckReportDTO
    {
        $definitions = [];
        $ids = [];
        foreach ($this->checks->getProvidedServices() as $service => $class) {
            // ContainerBuilder locators expose "?" as their type; autoconfigured services use
            // the concrete class as their ID in both the builder and the dumped container.
            $class = is_a($service, SystemCheckInterface::class, true) ? $service : $class;
            if (! is_a($class, SystemCheckInterface::class, true)) {
                throw new LogicException(sprintf('System check "%s" must declare its concrete service class.', $service));
            }
            $definition = $class::definition();
            if (isset($ids[$definition->id])) {
                throw new LogicException(sprintf('Duplicate system check ID "%s".', $definition->id));
            }
            $ids[$definition->id] = true;
            if (in_array($profile, $definition->profiles, true) || ($profile === CheckProfile::Extended && in_array(CheckProfile::Page, $definition->profiles, true))) {
                $definitions[$service] = $definition;
            }
        }
        uasort($definitions, static fn (CheckDefinitionDTO $a, CheckDefinitionDTO $b): int => [$a->order, $a->id] <=> [$b->order, $b->id]);

        $sections = [];
        $resultIds = [];
        foreach ($definitions as $service => $definition) {
            try {
                $check = $this->checks->get($service);
                if (! $check instanceof SystemCheckInterface) {
                    throw new LogicException(sprintf('Service "%s" is not a system check.', $service));
                }
                $results = $check->run();
                if ($results === []) {
                    throw new LogicException(sprintf('System check "%s" returned no result.', $definition->id));
                }
            } catch (Throwable $exception) {
                $this->logger->error('System check failed.', ['check' => $definition->id, 'exception' => $exception]);
                $results = [new CheckResultDTO(
                    $definition->id,
                    $definition->name,
                    d__('system', 'Could not check'),
                    CheckStatus::Unavailable,
                    CheckSeverity::Warning,
                    d__('system', 'The check could not be completed. See the application log for details and run the check again.'),
                )];
            }
            foreach ($results as $result) {
                if (isset($resultIds[$result->id])) {
                    throw new LogicException(sprintf('Duplicate system check result ID "%s".', $result->id));
                }
                $resultIds[$result->id] = true;
            }
            $sections[$definition->group] ??= ['title' => $definition->group, 'icon' => $definition->icon, 'checks' => []];
            array_push($sections[$definition->group]['checks'], ...$results);
        }

        return new CheckReportDTO(array_values($sections), time(), PHP_SAPI);
    }
}
