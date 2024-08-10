<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PayoutRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PayoutCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PayoutCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Payout::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/payout');
        CRUD::setEntityNameStrings('payout', 'payouts');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('dj_id')->type('number')->label('DJ ID');
        CRUD::column('amount')->type('number')->label('Amount');
        CRUD::column('status')->type('enum')->label('Status');
        CRUD::column('payout_type')->type('enum')->label('Payout Type');
        CRUD::column('payout_details')->type('textarea')->label('Payout Details');
        CRUD::column('yookassa_payout_id')->type('text')->label('YooKassa Payout ID');
        CRUD::column('processed_at')->type('datetime')->label('Processed At');

        // You can add more columns here if needed
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PayoutRequest::class);

        CRUD::field('dj_id')->type('number')->label('DJ ID');
        CRUD::field('amount')->type('number')->label('Amount');
        CRUD::field('status')->type('enum')->label('Status');
        CRUD::field('payout_type')->type('enum')->label('Payout Type')->options([
            'bank_card' => 'Bank Card',
            'sbp' => 'SBP',
            'yoo_money' => 'YooMoney',
        ]);
        CRUD::field('payout_details')->type('textarea')->label('Payout Details');
        CRUD::field('yookassa_payout_id')->type('text')->label('YooKassa Payout ID');
        CRUD::field('processed_at')->type('datetime')->label('Processed At');

        // Add more fields as needed
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
