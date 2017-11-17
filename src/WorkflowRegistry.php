<?php

namespace Brexis\LaravelWorkflow;

use Brexis\LaravelWorkflow\Events\WorkflowSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\MarkingStore\MultipleStateMarkingStore;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\SupportStrategy\ClassInstanceSupportStrategy;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowRegistry
{
    /**
     * @var Symfony\Component\Workflow\Registry
     */
    private $registry;

    /**
     * @var array
     */
    private $config;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    public function __construct(array $config)
    {
        $this->registry     = new Registry();
        $this->config       = $config;
        $this->dispatcher   = new EventDispatcher();

        $subscriber         = new WorkflowSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        foreach ($this->config as $name => $workflowData) {
            $builder = new DefinitionBuilder($workflowData['places']);

            foreach ($workflowData['transitions'] as $transitionName => $transition) {
                if (!is_string($transitionName)) {
                    $transitionName = $transition['name'];
                }

                $builder->addTransition(new Transition($transitionName, $transition['from'], $transition['to']));
            }

            $definition     = $builder->build();
            $markingStore   = $this->getMakingStoreInstance($workflowData);
            $workflow       = $this->getWorkflowInstance($name, $workflowData, $definition, $markingStore);

            foreach ($workflowData['supports'] as $supportedClass) {
                $this->registry->add($workflow, new ClassInstanceSupportStrategy($supportedClass));
            }
        }
    }

    /**
     * Return the $subject workflo
     * @param  object $subject
     * @param  string $workflowName
     * @return Workflow
     */
    public function get($subject, $workflowName = null)
    {
        return $this->registry->get($subject, $workflowName);
    }

    /**
     * Add a workflow to the subject
     * @param Workflow $workflow
     * @param Symfony\Component\Workflow\SupportStrategy\SupportStrategyInterface $supportStrategy
     */
    public function add(Workflow $workflow, $supportStrategy)
    {
        return $this->registry->add($workflow, new ClassInstanceSupportStrategy($supportStrategy));
    }

    /**
     * Return the workflow instance
     *
     * @param  String                                                        $name
     * @param  array                                                         $workflowData
     * @param  Symfony\Component\Workflow\Definition                         $definition
     * @param  Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface $makingStore
     * @return Symfony\Component\Workflow\Workflow
     */
    private function getWorkflowInstance($name, $workflowData, Definition $definition, MarkingStoreInterface $markingStore)
    {
        $type  = isset($workflowData['type']) ? $workflowData['type'] : 'workflow';
        $className = Workflow::class;

        if ($type === 'state_machine') {
            $className = StateMachine::class;
        } else if (isset($workflowData['class'])) {
            $className = $workflowData['class'];
        }

        return new $className($definition, $markingStore, $this->dispatcher, $name);
    }

    /**
     * Return the making store instance
     *
     * @param  array $makingStoreData
     * @return Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface
     */
    private function getMakingStoreInstance($workflowData)
    {
        $makingStoreData    = isset($workflowData['marking_store']) ? $workflowData['marking_store'] : [];
        $type               = isset($makingStoreData['type']) ? $makingStoreData['type'] : 'single_state';
        $className          = SingleStateMarkingStore::class;
        $arguments          = [];

        if ($type === 'multiple_state') {
            $className = MultipleStateMarkingStore::class;
        } else if (isset($workflowData['class'])) {
            $className = $workflowData['class'];
        }

        if (isset($makingStoreData['arguments'])) {
            $arguments = $makingStoreData['arguments'];
        }

        $class = new \ReflectionClass($className);

        return $class->newInstanceArgs($arguments);
    }
}
