<?php

namespace Naxon\NovaFieldSortable\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class ResourceSortingController extends Controller
{
    public function handle(NovaRequest $request)
    {

        $request->findResourceOrFail()->authorizeToUpdate($request);
        
        $direction = $request->get('direction', null);
        $new_order = $request->get('setNewOrder', null);

        if ($direction)
            return $this->reorderByDirection($request, $direction);
        elseif ( $new_order ) 
            return $this->reorderByArray($request, $new_order);
        else 
            return response('', 500);
        
    }

    /**
     * Reorder by direction
     *
     * @param [type] $request
     * @param [type] $direction
     * @return void
     */
    public function reorderByDirection($request, $direction) {

        if (!in_array($direction, ['up', 'down'])) {
            return response('', 500);
        }

        $model = $request->findModelQuery()->firstOrFail();

        if (!method_exists($model, 'moveOrderUp') || !method_exists($model, 'moveOrderDown')) {
            return response(__('Missing sorting methods on model :model', [
                'model' => get_class($model)
            ]), 500);
        }

        if ($direction == 'up') {
            $model->moveOrderUp();
        } else {
            $model->moveOrderDown();
        }

        return response('', 200);
    }

    /**
     * Reorder by drag and drop
     *
     * @param [type] $request
     * @param [type] $new_order
     * @return void
     */
    public function reorderByArray($request, $new_order) {

        if (!array_key_exists('resourcesArray',$new_order) || !array_key_exists('oldPosition',$new_order) || !array_key_exists('newPosition',$new_order)) {
            return response('', 500);
        }

        $resourcesArray = (array) $new_order['resourcesArray'];
        $oldPos = (int) $new_order['oldPosition'];
        $newPos = (int) $new_order['newPosition'];

        /**
         * costruisco l'array di ID da riordinare
         * https://github.com/spatie/eloquent-sortable
         * 
         * example: MyModel::setNewOrder([3,1,2], 10);
         */ 
        $sortable_column = $request->model()->sortable['order_column_name'];
        $arrayToReorder = array();
        
        if( ($newPos - $oldPos) > 0 ) {

            foreach (range($oldPos, $newPos) as $index) {
                $arrayToReorder[] = $resourcesArray[$index]['id']['value'];
            }
            $firstModelOrder = $request->findModelQuery()->firstOrFail()->{$sortable_column};

        } else {

            foreach (range($newPos, $oldPos) as $index) {
                $arrayToReorder[] = $resourcesArray[$index]['id']['value'];
            }
            $firstModelOrder = $request->model()::find($arrayToReorder[1])->{$sortable_column};
        }

        // riordino l'array partendo dall'indice di ordine più basso
        $request->model()::setNewOrder($arrayToReorder, $firstModelOrder );

        return response('', 200);
    }
}
