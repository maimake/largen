<?php

namespace App\Observers;

use App\User;

class UserObserver
{

    /**
     * Listen to the created event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function created(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


    /**
     * Listen to the creating event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function creating(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
        $model->forceFill([
            'api_token' => token_generate(),
        ]);
    }


    /**
     * Listen to the updating event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function updating(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


    /**
     * Listen to the updated event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function updated(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


    /**
     * Listen to the saving event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function saving(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


    /**
     * Listen to the saved event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function saved(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


    /**
     * Listen to the deleting event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function deleting(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


    /**
     * Listen to the deleted event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function deleted(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


    /**
     * Listen to the restoring event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function restoring(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


    /**
     * Listen to the restored event.
     * Called when operates a model, while won't called via executing a sql directly.
     *
     * @param  User  $model
     * @return void
     */
    public function restored(User $model)
    {
        //\Log::debug(__FUNCTION__ . ': User');
    }


}
