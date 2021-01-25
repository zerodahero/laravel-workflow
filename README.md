# Laravel workflow [![Build Status](https://travis-ci.org/zerodahero/laravel-workflow.svg?branch=master)](https://travis-ci.org/zerodahero/laravel-workflow)

This is a fork from [brexis/laravel-workflow](https://github.com/brexis/laravel-workflow). My current needs for this package are a bit more bleeding-edge than seem to be maintainable by the other packages. Massive kudos to brexis for the original work and adaptation on this.

Use the Symfony Workflow component in Laravel

## Installation

    composer require zerodahero/laravel-workflow

## Laravel Support

| Package Version | Laravel Version Support |
| --- | --- |
| ^2.0 | 5.x |
| ^3.0 | 7.x |
| ^3.2 | 8.x |

## Upgrade from v2 to v3

The biggest changes from v2 to v3 are the dependencies. To match the Symfony v5 components, the Laravel version is raised to v7. If you're on Laravel v6 or earlier, you should continue to use the v2 releases of this package.

To match the changes in the Symfony v5 workflow component, the "arguments" config option has been changed to "property". This describes the property on the model the workflow ties to (in most circumstances, you can simply change the key name from "arguments" to "property", and set to a string instead of the previous array).

Also, the "initial_place" key has been changed to "initial_places" to align with the Symfony component as well.

### Non-package Discovery

If you aren't using package discovery:

Add a ServiceProvider to your providers array in `config/app.php`:

```php
<?php

'providers' => [
    ...
    ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider::class,

]
```

Add the `Workflow` facade to your facades array:

```php
<?php
    ...
    'Workflow' => ZeroDaHero\LaravelWorkflow\Facades\WorkflowFacade::class,
```

## Configuration

Publish the config file

```bash
php artisan vendor:publish --provider="ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider"
```

Configure your workflow in `config/workflow.php`

```php
<?php

// Full workflow, annotated.
return [
    // Name of the workflow is the key
    'straight' => [
        'type' => 'workflow', // or 'state_machine', defaults to 'workflow' if omitted
        // The marking store can be omitted, and will default to 'multiple_state'
        // for workflow and 'single_state' for state_machine if the type is omitted
        'marking_store' => [
            'type' => 'multiple_state', // or 'single_state', can be omitted to default to workflow type's default
            'property' => 'marking', // this is the property on the model, defaults to 'marking'
            'class' => MethodMarkingStore::class, // optional, uses EloquentMethodMarkingStore by default (for Eloquent models)
        ],
        // optional top-level metadata
        'metadata' => [
            // any data
        ],
        'supports' => ['App\BlogPost'], // objects this workflow supports
        // Specifies events to dispatch (only in 'workflow', not 'state_machine')
        // - set `null` to dispatch all events (default, if omitted)
        // - set to empty array (`[]`) to dispatch no events
        // - set to array of events to dispatch only specific events
        // Note that announce will dispatch a guard event on the next transition
        // (if announce isn't dispatched the next transition won't guard until checked/applied)
        'events_to_dispatch' => [
           Symfony\Component\Workflow\WorkflowEvents::ENTER,
           Symfony\Component\Workflow\WorkflowEvents::LEAVE,
           Symfony\Component\Workflow\WorkflowEvents::TRANSITION,
           Symfony\Component\Workflow\WorkflowEvents::ENTERED,
           Symfony\Component\Workflow\WorkflowEvents::COMPLETED,
           Symfony\Component\Workflow\WorkflowEvents::ANNOUNCE,
        ],
        'places' => ['draft', 'review', 'rejected', 'published'],
        'initial_places' => ['draft'], // defaults to the first place if omitted
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => 'review',
                // optional transition-level metadata
                'metadata' => [
                    // any data
                ]
            ],
            'publish' => [
                'from' => 'review',
                'to' => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to' => 'rejected'
            ]
        ],
    ]
];
```

A more minimal setup (for a workflow on an eloquent model).

```php
<?php

// Simple workflow. Sets type 'workflow', with a 'multiple_state' marking store
// on the 'marking' property of any 'App\BlogPost' model.
return [
    'simple' => [
        'supports' => ['App\BlogPost'], // objects this workflow supports
        'places' => ['draft', 'review', 'rejected', 'published'],
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => 'review'
            ],
            'publish' => [
                'from' => 'review',
                'to' => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to' => 'rejected'
            ]
        ],
    ]
];
```

If you are using a "multiple_state" type of marking_store (i.e. you will be in multiple places simultaneously in your workflow), you will need your supported class/Eloquent model to cast the marking to an array. Read more in the [Laravel docs](https://laravel.com/docs/5.8/eloquent-mutators#array-and-json-casting).


You may also add in metadata, similar to the Symfony implementation (note: it is not collected the same way as Symfony's implementation, but should work the same. Please open a pull request or issue if that's not the case.)

```php
<?php

return [
    'straight' => [
        'type' => 'workflow', // or 'state_machine'
        'metadata' => [
            'title' => 'Blog Publishing Workflow',
        ],
        'marking_store' => [
            'type' => 'multiple_state', // or 'single_state'
            'property' => 'currentPlace' // this is the property on the model
        ],
        'supports' => ['App\BlogPost'],
        'places' => [
            'draft' => [
                'metadata' => [
                    'max_num_of_words' => 500,
                ]
            ]
            'review',
            'rejected',
            'published'
        ],
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => 'review',
                'metadata' => [
                    'priority' => 0.5,
                ]
            ],
            'publish' => [
                'from' => 'review',
                'to' => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to' => 'rejected'
            ]
        ],
    ]
];
```

Use the `WorkflowTrait` inside supported classes

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ZeroDaHero\LaravelWorkflow\Traits\WorkflowTrait;

class BlogPost extends Model
{
  use WorkflowTrait;

}
```
## Usage

```php
<?php

use App\BlogPost;
use Workflow;

$post = BlogPost::find(1);
$workflow = Workflow::get($post);
// if more than one workflow is defined for the BlogPost class
$workflow = Workflow::get($post, $workflowName);
// or get it directly from the trait
$workflow = $post->workflow_get();
// if more than one workflow is defined for the BlogPost class
$workflow = $post->workflow_get($workflowName);

$workflow->can($post, 'publish'); // False
$workflow->can($post, 'to_review'); // True
$transitions = $workflow->getEnabledTransitions($post);

// Apply a transition
$workflow->apply($post, 'to_review');
$post->save(); // Don't forget to persist the state

// Get the workflow directly

// Using the WorkflowTrait
$post->workflow_can('publish'); // True
$post->workflow_can('to_review'); // False

// Get the post transitions
foreach ($post->workflow_transitions() as $transition) {
    echo $transition->getName();
}

// Apply a transition
$post->workflow_apply('publish');
$post->save();
```

## Symfony Workflow Usage
Once you have the underlying Symfony workflow component, you can do anything you want, just like you would in Symfony. A couple examples are provided below, but be sure to take a look at the [Symfony docs](https://symfony.com/doc/current/workflow.html) to better understand what's going on here.

```php
<?php

use App\Blogpost;
use Workflow;

$post = BlogPost::find(1);
$workflow = $post->workflow_get();

// Get the current places
$places = $workflow->getMarking($post)->getPlaces();

// Get the definition
$definition = $workflow->getDefinition();

// Get the metadata
$metadata = $workflow->getMetadataStore();
// or get a specific piece of metadata
$workflowMetadata = $workflow->getMetadataStore()->getWorkflowMetadata();
$placeMetadata = $workflow->getMetadataStore()->getPlaceMetadata($place); // string place name
$transitionMetadata = $workflow->getMetadataStore()->getTransitionMetadata($transition); // transition object
// or by key
$otherPlaceMetadata = $workflow->getMetadataStore()->getMetadata('max_num_of_words', 'draft');
```

### Use the events
This package provides a list of events fired during a transition

```php
    ZeroDaHero\LaravelWorkflow\Events\Guard
    ZeroDaHero\LaravelWorkflow\Events\Leave
    ZeroDaHero\LaravelWorkflow\Events\Transition
    ZeroDaHero\LaravelWorkflow\Events\Enter
    ZeroDaHero\LaravelWorkflow\Events\Entered
```

You can subscribe to an event

```php
<?php

namespace App\Listeners;

use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;

class BlogPostWorkflowSubscriber
{
    /**
     * Handle workflow guard events.
     */
    public function onGuard(GuardEvent $event)
    {
        /** Symfony\Component\Workflow\Event\GuardEvent */
        $originalEvent = $event->getOriginalEvent();

        /** @var App\BlogPost $post */
        $post = $originalEvent->getSubject();
        $title = $post->title;

        if (empty($title)) {
            // Posts with no title should not be allowed
            $originalEvent->setBlocked(true);
        }
    }

    /**
     * Handle workflow leave event.
     */
    public function onLeave($event)
    {
        // The event can also proxy to the original event
        $subject = $event->getSubject();
        // is the same as:
        $subject = $event->getOriginalEvent()->getSubject();
    }

    /**
     * Handle workflow transition event.
     */
    public function onTransition($event) {}

    /**
     * Handle workflow enter event.
     */
    public function onEnter($event) {}

    /**
     * Handle workflow entered event.
     */
    public function onEntered($event) {}

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\GuardEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onGuard'
        );

        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\LeaveEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onLeave'
        );

        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\TransitionEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onTransition'
        );

        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\EnterEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onEnter'
        );

        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\EnteredEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onEntered'
        );
    }

}
```

You are also welcome to use [Symfony's dot syntax style of event emission](https://symfony.com/doc/current/workflow.html#using-events).

NOTE: these events receive the Symfony event prior to version 3.1.1, and will receive this package's events starting with version 3.1.1

```php
<?php

namespace App\Listeners;

use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;

class BlogPostWorkflowSubscriber
{
    // ...

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        // can use any of the three formats:
        // workflow.guard
        // workflow.[workflow name].guard
        // workflow.[workflow name].guard.[transition name]
        $events->listen(
            'workflow.straight.guard',
            'App\Listeners\BlogPostWorkflowSubscriber@onGuard'
        );

        // workflow.leave
        // workflow.[workflow name].leave
        // workflow.[workflow name].leave.[place name]
        $events->listen(
            'workflow.straight.leave',
            'App\Listeners\BlogPostWorkflowSubscriber@onLeave'
        );

        // workflow.transition
        // workflow.[workflow name].transition
        // workflow.[workflow name].transition.[transition name]
        $events->listen(
            'workflow.straight.transition',
            'App\Listeners\BlogPostWorkflowSubscriber@onTransition'
        );

        // workflow.enter
        // workflow.[workflow name].enter
        // workflow.[workflow name].enter.[place name]
        $events->listen(
            'workflow.straight.enter',
            'App\Listeners\BlogPostWorkflowSubscriber@onEnter'
        );

        // workflow.entered
        // workflow.[workflow name].entered
        // workflow.[workflow name].entered.[place name]
        $events->listen(
            'workflow.straight.entered',
            'App\Listeners\BlogPostWorkflowSubscriber@onEntered'
        );

        // workflow.completed
        // workflow.[workflow name].completed
        // workflow.[workflow name].completed.[transition name]
        $events->listen(
            'workflow.straight.completed',
            'App\Listeners\BlogPostWorkflowSubscriber@onCompleted'
        );

        // workflow.announce
        // workflow.[workflow name].announce
        // workflow.[workflow name].announce.[transition name]
        $events->listen(
            'workflow.straight.announce',
            'App\Listeners\BlogPostWorkflowSubscriber@onAnnounce'
        );
    }
}
```

## Workflow vs State Machine

When using a multi-state workflow, it becomes necessary to distinguish between an array of multiple places that can transition to one place, or a situation where a subject in exactly multiple places transitions to one. Since the config is a PHP array, you must "nest" the latter situation into an array, so that it builds a transition using an array of places, rather that looping through single places.

### Example 1. Exactly two places transition to one

In this example, a draft must be in both `content_approved` and `legal_approved` at the same time

```php
<?php

return [
    'straight' => [
        'type' => 'workflow',
        'metadata' => [
            'title' => 'Blog Publishing Workflow',
        ],
        'marking_store' => [
            'type' => 'multiple_state',
            'property' => 'currentPlace'
        ],
        'supports' => ['App\BlogPost'],
        'places' => [
            'draft',
            'content_review',
            'content_approved',
            'legal_review',
            'legal_approved',
            'published'
        ],
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => ['content_review', 'legal_review'],
            ],
            // ... transitions to "approved" states here
            'publish' => [
                'from' => [ // note array in array
                    ['content_review', 'legal_review']
                ],
                'to' => 'published'
            ],
            // ...
        ],
    ]
];
```

### Example 2. Either of two places transition to one

In this example, a draft can transition from EITHER `content_approved` OR `legal_approved` to `published`

```php
<?php

return [
    'straight' => [
        'type' => 'workflow',
        'metadata' => [
            'title' => 'Blog Publishing Workflow',
        ],
        'marking_store' => [
            'type' => 'multiple_state',
            'property' => 'currentPlace'
        ],
        'supports' => ['App\BlogPost'],
        'places' => [
            'draft',
            'content_review',
            'content_approved',
            'legal_review',
            'legal_approved',
            'published'
        ],
        'transitions' => [
            'to_review' => [
                'from' => 'draft',
                'to' => ['content_review', 'legal_review'],
            ],
            // ... transitions to "approved" states here
            'publish' => [
                'from' => [
                    'content_review',
                    'legal_review'
                ],
                'to' => 'published'
            ],
            // ...
        ],
    ]
];
```

## Dump Workflows
Symfony workflow uses GraphvizDumper to create the workflow image. You may need to install the `dot` command of [Graphviz](http://www.graphviz.org/)

    php artisan workflow:dump workflow_name --class App\\BlogPost

You can change the image format with the `--format` option. By default the format is png.

    php artisan workflow:dump workflow_name --format=jpg

If you would like to output to a different directory than root, you can use the `--disk` and `--path` options to set the Storage disk (`local` by default) and path (`root_path()` by default).

    php artisan workflow:dump workflow-name --class=App\\BlogPost --disk=s3 --path="workflows/diagrams/"

## Use in tracking mode

If you are loading workflow definitions through some dynamic means (perhaps via DB), you'll most likely want to turn on registry tracking. This will enable you to see what has been loaded, to prevent or ignore duplicate workflow definitions.

Set `track_loaded` to `true` in the `workflow_registry.php` config file.

```php
<?php

return [

    /**
     * When set to true, the registry will track the workflows that have been loaded.
     * This is useful when you're loading from a DB, or just loading outside of the
     * main config files.
     */
    'track_loaded' => false,

    /**
     * Only used when track_loaded = true
     *
     * When set to true, a registering a duplicate workflow will be ignored (will not load the new definition)
     * When set to false, a duplicate workflow will throw a DuplicateWorkflowException
     */
    'ignore_duplicates' => false,

];
```

You can dynamically load a workflow by using the `addFromArray` method on the workflow registry

```php
<?php

    /**
     * Load the workflow type definition into the registry
     */
    protected function loadWorkflow()
    {
        $registry = app()->make('workflow');
        $workflowName = 'straight';
        $workflowDefinition = [
            // Workflow definition here
            // (same format as config/symfony docs)
            // This should be the definition only,
            // not including the key for the name.
            // See note below on initial_places for an example.
        ];

        $registry->addFromArray($workflowName, $workflowDefinition);

        // or if catching duplicates

        try {
            $registry->addFromArray($workflowName, $workflowDefinition);
        } catch (DuplicateWorkflowException $e) {
            // already loaded
        }
    }
```

NOTE: There's no persistence for dynamic workflows, this package assumes you're storing those somehow (DB, etc). To use the dynamic workflows, you will need to load the workflow prior to using it. The `loadWorkflow()` method above could be tied into a model `boot()` or similar.

You may also specify an `initial_places` in your workflow definition, if it is not the first place in the "places" list.

```php
<?php

return [
    'type' => 'workflow', // or 'state_machine'
    'metadata' => [
        'title' => 'Blog Publishing Workflow',
    ],
    'marking_store' => [
        'type' => 'multiple_state',
        'property' => 'currentPlace'
    ],
    'supports' => ['App\BlogPost'],
    'places' => [
        'review',
        'rejected',
        'published',
        'draft', => [
            'metadata' => [
                'max_num_of_words' => 500,
            ]
        ]
    ],
    'initial_places' => 'draft', // or set to an array if multiple initial places
    'transitions' => [
        'to_review' => [
            'from' => 'draft',
            'to' => 'review',
            'metadata' => [
                'priority' => 0.5,
            ]
        ],
        'publish' => [
            'from' => 'review',
            'to' => 'published'
        ],
        'reject' => [
            'from' => 'review',
            'to' => 'rejected'
        ]
    ],
];
```
