<?php

namespace ZeroDaHero\LaravelWorkflow;

use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use ZeroDaHero\LaravelWorkflow\Events\WorkflowSubscriber;
use Symfony\Component\Workflow\Metadata\InMemoryMetadataStore;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\MarkingStore\MultipleStateMarkingStore;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;

/**
 * @author Boris Koumondji <brexis@yahoo.fr>
 */
class WorkflowRegistry
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * WorkflowRegistry constructor
     *
     * @param  array $config
     * @throws \ReflectionException
     */
    public function __construct(array $config)
    {
        $this->registry = new Registry();
        $this->config = $config;
        $this->dispatcher = new EventDispatcher();

        $subscriber = new WorkflowSubscriber();
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
     * @param Workflow $workflow
     * @param string   $supportStrategy
     *
     * @return void
     */
    public function add(Workflow $workflow, $supportStrategy)
    {
        $this->registry->addWorkflow($workflow, new InstanceOfSupportStrategy($supportStrategy));
    }

    /**
     * Add a workflow to the registry from array
     *
     * @param  string $name
     * @param  array  $workflowData
     * @throws \ReflectionException
     *
     * @return void
     */
    public function addFromArray($name, array $workflowData)
    {
        $metadata = $this->extractWorkflowPlacesMetaData($workflowData);

        $builder = new DefinitionBuilder($workflowData['places']);

        foreach ($workflowData['transitions'] as $transitionName => $transition) {
            if (!is_string($transitionName)) {
                $transitionName = $transition['name'];
            }

            foreach ((array)$transition['from'] as $form) {
                $transitionObj = new Transition($transitionName, $form, $transition['to']);
                $builder->addTransition($transitionObj);

                if (isset($transition['metadata'])) {
                    $metadata['transitions']->attach($transitionObj, $transition['metadata']);
                }
            }
        }

        $metadataStore = new InMemoryMetadataStore(
            $metadata['workflow'],
            $metadata['places'],
            $metadata['transitions']
        );

        $builder->setMetadataStore($metadataStore);

        $definition = $builder->build();
        $markingStore = $this->getMarkingStoreInstance($workflowData);
        $workflow = $this->getWorkflowInstance($name, $workflowData, $definition, $markingStore);

        foreach ($workflowData['supports'] as $supportedClass) {
            $this->add($workflow, $supportedClass);
        }
    }

    /**
     * Return the workflow instance
     *
     * @param  string                $name
     * @param  array                 $workflowData
     * @param  Definition            $definition
     * @param  MarkingStoreInterface $markingStore
     * @return Workflow
     */
    protected function getWorkflowInstance(
        $name,
        array $workflowData,
        Definition $definition,
        MarkingStoreInterface $markingStore
    ) {
        if (isset($workflowData['class'])) {
            $className = $workflowData['class'];
        } elseif (isset($workflowData['type']) && $workflowData['type'] === 'state_machine') {
            $className = StateMachine::class;
        } else {
            $className = Workflow::class;
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
    protected function getMarkingStoreInstance(array $workflowData)
    {
        $markingStoreData = isset($workflowData['marking_store']) ? $workflowData['marking_store'] : [];
        $arguments = isset($markingStoreData['arguments']) ? $markingStoreData['arguments'] : [];

        if (isset($markingStoreData['class'])) {
            $className = $markingStoreData['class'];
        } elseif (isset($markingStoreData['type']) && $markingStoreData['type'] === 'multiple_state') {
            $className = MultipleStateMarkingStore::class;
        } else {
            $className = SingleStateMarkingStore::class;
        }

        $class = new \ReflectionClass($className);

        return $class->newInstanceArgs($arguments);
    }

    /**
     * Extracts workflow and places metadata from the config
     * NOTE: This modifies the provided config!
     *
     * @param array $workflowData
     *
     * @return array
     */
    protected function extractWorkflowPlacesMetaData(array &$workflowData)
    {
        $metadata = [
            'workflow' => [],
            'places' => [],
            'transitions' => new \SplObjectStorage
        ];

        if (isset($workflowData['metadata'])) {
            $metadata['workflow'] = $workflowData['metadata'];
            unset($workflowData['metadata']);
        }

        foreach ($workflowData['places'] as $key => &$place) {
            if (is_int($key) && !is_array($place)) {
                // no metadata, just place name
                continue;
            }

            if (isset($place['metadata'])) {
                if (is_int($key) && !$place['name']) {
                    throw new InvalidArgumentException(sprintf('Unknown name for place at index %d', $key));
                }

                $name = !is_int($key) ? $key : $place['name'];
                $metadata['places'][$name] = $place['metadata'];

                $place = $name;
            }
        }

        return $metadata;
    }
}
