<?php

namespace Filament\Forms\Concerns;

use Illuminate\Database\Eloquent\Model;

trait BelongsToModel
{
    public Model | string | null $model = null;

    public function model(Model | string | null $model = null): static
    {
        $this->model = $model;

        return $this;
    }

    public function saveRelationships(?array $exceptComponents = []): void
    {
        foreach ($this->getComponents(withHidden: true) as $component) {
            if (method_exists($component, 'getName') && in_array($component->getName(), $exceptComponents)) {
                continue;
            }
            
            $component->saveRelationshipsBeforeChildren();

            $shouldSaveRelationshipsWhenDisabled = $component->shouldSaveRelationshipsWhenDisabled();

            foreach ($component->getChildComponentContainers(withHidden: $component->shouldSaveRelationshipsWhenHidden()) as $container) {
                if ((! $shouldSaveRelationshipsWhenDisabled) && $container->isDisabled()) {
                    continue;
                }

                $container->saveRelationships($exceptComponents);
            }

            $component->saveRelationships();
        }
    }

    public function loadStateFromRelationships(bool $andHydrate = false, ?array $exceptComponents = []): void
    {
        foreach ($this->getComponents(withHidden: true) as $component) {
            if (method_exists($component, 'getName') && in_array($component->getName(), $exceptComponents)) {
                continue;
            }
            
            $component->loadStateFromRelationships($andHydrate);

            foreach ($component->getChildComponentContainers(withHidden: true) as $container) {
                $container->loadStateFromRelationships($andHydrate);
            }
        }
    }

    public function getModel(): ?string
    {
        $model = $this->model;

        if ($model instanceof Model) {
            return $model::class;
        }

        if (filled($model)) {
            return $model;
        }

        return $this->getParentComponent()?->getModel();
    }

    public function getRecord(): ?Model
    {
        $model = $this->model;

        if ($model instanceof Model) {
            return $model;
        }

        if (is_string($model)) {
            return null;
        }

        return $this->getParentComponent()?->getRecord();
    }

    public function getModelInstance(): ?Model
    {
        $model = $this->model;

        if ($model === null) {
            return $this->getParentComponent()?->getModelInstance();
        }

        if ($model instanceof Model) {
            return $model;
        }

        return app($model);
    }
}
