<?php


namespace App\Services;


use App\Models\Operator;

class MoneyInService
{
    protected $paydunyaService;

    /**
     * MoneyInService constructor.
     * @param $paydunyaService
     */
    public function __construct(PaydunyaService $paydunyaService)
    {
        $this->paydunyaService = $paydunyaService;
    }


    public function initialise($data){

        $operator=Operator::find($data['operator_id']);
        $amount=$data['amount'];
        $referenceId=$data['referenceId'];
        $phone=$data['phone'];
        $this->paydunyaService->createOrder([
           'product_name'=>$data['product_name'],
           'description'=>$data['description'],
           'amount'=>$amount,
           'referenceId'=>$referenceId,
           'phone'=>$phone
        ]);

    }


}
