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
use Symfony\Component\Workflow\SupportStrategy\SupportStrategyInterface;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\SupportStrategy\ClassInstanceSupportStrategy;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowRegistry
{
    /**
     * @var Registry
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

    /**
     * WorkflowRegistry constructor
     *
     * @param  array $config
     * @throws \ReflectionException
     */
    public function __construct(array $config)
    {
        $this->registry   = new Registry();
        $this->config     = $config;
        $this->dispatcher = new EventDispatcher();

        $subscriber       = new WorkflowSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        foreach ($this->config as $name => $workflowData) {
            $this->addFromArray($name, $workflowData);
        }
    }

    /**
     * Return the $subject workflow
     *
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
     *
     * @param Workflow                 $workflow
     * @param SupportStrategyInterface $supportStrategy
     */
    public function add(Workflow $workflow, SupportStrategyInterface $supportStrategy)
    {
        $this->registry->add($workflow, new ClassInstanceSupportStrategy($supportStrategy));
    }

    /**
     * Add a workflow to the registry from array
     *
     * @param  string $name
     * @param  array  $workflowData
     * @throws \ReflectionException
     */
    public function addFromArray($name, array $workflowData)
    {
        $builder = new DefinitionBuilder($workflowData['places']);

        foreach ($workflowData['transitions'] as $transitionName => $transition) {
            if (!is_string($transitionName)) {
                $transitionName = $transition['name'];
            }

            $builder->addTransition(new Transition($transitionName, $transition['from'], $transition['to']));
        }

        $definition   = $builder->build();
        $markingStore = $this->getMarkingStoreInstance($workflowData);
        $workflow     = $this->getWorkflowInstance($name, $workflowData, $definition, $markingStore);

        foreach ($workflowData['supports'] as $supportedClass) {
            $this->add($workflow, $supportedClass);
        }
    }

    /**
     * Return the workflow instance
     *
     * @param  String                $name
     * @param  array                 $workflowData
     * @param  Definition            $definition
     * @param  MarkingStoreInterface $markingStore
     * @return Workflow
     */
    private function getWorkflowInstance(
        $name,
        array $workflowData,
        Definition $definition,
        MarkingStoreInterface $markingStore
    ) {
        $type  = isset($workflowData['type']) ? $workflowData['type'] : 'workflow';
        $className = Workflow::class;

        if ($type === 'state_machine') {
            $className = StateMachine::class;
        } elseif (isset($workflowData['class'])) {
            $className = $workflowData['class'];
        }

        return new $className($definition, $markingStore, $this->dispatcher, $name);
    }

    /**
     * Return the making store instance
     *
     * @param  array $workflowData
     * @return MarkingStoreInterface
     * @throws \ReflectionException
     */
    private function getMarkingStoreInstance(array $workflowData)
    {
        $markingStoreData = isset($workflowData['marking_store']) ? $workflowData['marking_store'] : [];
        $type             = isset($markingStoreData['type']) ? $markingStoreData['type'] : 'single_state';
        $className        = SingleStateMarkingStore::class;
        $arguments        = isset($markingStoreData['arguments']) ? $markingStoreData['arguments'] : [];

        if ($type === 'multiple_state') {
            $className = MultipleStateMarkingStore::class;
        } elseif (isset($markingStoreData['class'])) {
            $className = $markingStoreData['class'];
        }

        $class = new \ReflectionClass($className);

        return $class->newInstanceArgs($arguments);
    }
}
