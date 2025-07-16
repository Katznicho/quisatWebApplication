<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Payments\YoAPI;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\Exception;

class YoPayments extends Controller
{
    public function makePayment(Request $request)
    {
        try {
            $grandTotal = $request->grandTotal;
            $productIds = json_decode($request->productIds);
            $quantities = json_decode($request->productQuantities);
            $customer_id = $request->customer_id;

            $customer = Customer::find($customer_id);
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            // Modify phone number: remove leading 0 and append 256
            $phone = $customer->Phone;
            if (Str::startsWith($phone, '0')) {
                $phone = '256' . substr($phone, 1);
            }

            // Calculate the service charge
            $commissionPercentage = Auth::user()->entity->Commission ?? 0;
            $serviceCharge = ($commissionPercentage / 100) * $grandTotal;

            $description = 'Payment of ' . $grandTotal . ' for reference number: ' . Str::uuid();
            $status = config('status.payment_status.pending');

            $sale = Sale::create([
                'product_id' => json_encode($productIds),
                'quantities' => json_encode($quantities),
                'amount' => $grandTotal,
                'user_id' => auth()->id(),
                'entity_id' => auth()->user()->entity_id,
                'reference' => Str::uuid(),
                'status' => $status,
                'description' => $description,
                'phone_number' => $customer->Phone,
                'payment_mode' => 'Mobile Money',
                'OrderNotificationType' => 'SMS',
                'order_tracking_id' => Str::uuid(),
                'type' => 'Deposit',
                'payment_method' => 'USSD',
                'customer_id' => $customer_id,
                'service_charge' => $serviceCharge,  // Save the service charge
            ]);

            // Save Sale Items
            foreach ($productIds as $index => $productId) {
                $product = Product::find($productId);
                if ($product) {
                    SaleItem::create([
                        'SaleID' => $sale->id,
                        'ProductID' => $productId,
                        'Quantity' => $quantities[$index],
                        'Price' => $product->Price,
                        'Status' => 0
                    ]);
                }
            }

            // Identify recipients: users whose department_id matches any service_point_id of the selected products
            $servicePointIds = Product::whereIn('id', $productIds)->pluck('service_point_id');
            $recipients = User::whereIn('department_id', $servicePointIds)->get();

            foreach ($recipients as $recipient) {
                Notification::make()
                    ->title('New Client Alert')
                    ->icon('heroicon-o-document-text')
                    ->sendToDatabase($recipient)
                    ->success()
                    ->body('A new client with name ' . $customer->FirstName . ' '. $customer->LastName . ' Client ID: '. $customer->ClientID . 'and Visit ID '. $customer->NewVisitNumber . ' has been assigned to you ' . now() . ' by ' . auth()->user()->name)
                    ->actions([
                        Action::make('View Client Details')
                            ->button()
                            ->url(route('sale-items.index', $sale->id), shouldOpenInNewTab: true),
                    ])
                    ->send();
            }

            // Yo Payments API Interaction...
            $username = '100589248779';
            $password = 'bVXo-BDBw-KF5x-JSAS-9tm0-jORW-rYqX-7EGn';

            $YoPayments = new YoAPI($username, $password);
            $YoPayments->set_instant_notification_url('https://webhook.site/396126eb-cc9b-4c57-a7a9-58f43d2b7935');
            $YoPayments->set_external_reference(time());

            $res = $YoPayments->ac_deposit_funds($phone, $grandTotal, $description);

            $transactionReference = $res['TransactionReference'] ?? null;
            if ($transactionReference) {
                $sale->update(['reference' => $transactionReference]);
            } else {
                Log::error('YoPayments: Missing TransactionReference', ['response' => $res]);
                return redirect()->route('sales.create')->with('error', 'Payment initiation failed. Please try again.');
            }

            session()->flash('success', 'Payment request sent successfully!');

            return redirect()->route('sales.create');
        } catch (\Exception $e) {
            Log::error('YoPayments: makePayment error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkPaymentStatus($transactionReference)
    {
        try {
            // $username = config('yopay.username'); // Load from config or env
            // $password = config('yopay.password'); // Load from config or env



            $YoPayments = new YoAPI($username, $password);
            $statusCheck = $YoPayments->ac_transaction_check_status($transactionReference);

            $sale = Sale::where('reference', $transactionReference)->first();
            if ($sale) {
                $sale->update(['status' => $statusCheck['TransactionStatus']]);
            } else {
                Log::warning('Sale not found for transaction reference', ['reference' => $transactionReference]);
            }

            return response()->json(['status' => $statusCheck['TransactionStatus']]);
        } catch (\Exception $e) {
            Log::error('YoPayments: checkPaymentStatus error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
