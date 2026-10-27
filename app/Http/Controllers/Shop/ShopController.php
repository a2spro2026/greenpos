<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function home(string $shopSlug): View
    {
        $company = $this->company($shopSlug);
        $products = Product::query()
            ->forCompany($company->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->paginate(24);

        return view('shop.home', compact('company', 'products'));
    }

    public function addToCart(Request $request, string $shopSlug): RedirectResponse
    {
        $company = $this->company($shopSlug);
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $product = Product::query()->forCompany($company->id)->whereKey($data['product_id'])->firstOrFail();
        $cart = session()->get($this->cartKey($company->id), []);
        $id = (string) $product->id;
        $cart[$id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->sale_price,
            'tax_rate' => (float) $product->tax_rate,
            'quantity' => ($cart[$id]['quantity'] ?? 0) + (float) $data['quantity'],
        ];
        session()->put($this->cartKey($company->id), $cart);

        return back()->with('success', 'Produit ajouté au panier.');
    }

    public function cart(string $shopSlug): View
    {
        $company = $this->company($shopSlug);
        $cart = session()->get($this->cartKey($company->id), []);

        return view('shop.cart', compact('company', 'cart'));
    }

    public function checkoutForm(string $shopSlug): View
    {
        $company = $this->company($shopSlug);
        $cart = session()->get($this->cartKey($company->id), []);
        abort_if(empty($cart), 302, '', ['Location' => route('shop.home', $shopSlug)]);

        return view('shop.checkout', compact('company', 'cart'));
    }

    public function checkout(Request $request, string $shopSlug, StockService $stock): RedirectResponse
    {
        $company = $this->company($shopSlug);
        $cart = session()->get($this->cartKey($company->id), []);
        abort_if(empty($cart), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'payment' => ['required', 'in:pay_on_delivery,card_stub'],
        ]);

        $sale = DB::transaction(function () use ($company, $cart, $data, $stock) {
            $customer = null;
            if (! empty($data['email']) || ! empty($data['phone'])) {
                $customer = Customer::query()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'email' => $data['email'] ?: null,
                    ],
                    [
                        'name' => $data['name'],
                        'phone' => $data['phone'] ?? null,
                        'address' => $data['address'] ?? null,
                        'status' => 'active',
                        'type' => 'individual',
                        'code' => 'WEB-'.strtoupper(substr(md5($data['email'] ?? $data['phone'] ?? uniqid()), 0, 6)),
                    ]
                );
            }

            $storeId = $company->stores()->value('id');
            $seq = Sale::query()->forCompany($company->id)->count() + 1;
            $subtotal = 0;
            $tax = 0;
            foreach ($cart as $line) {
                $lineHt = $line['price'] * $line['quantity'];
                $lineTax = $lineHt * ($line['tax_rate'] / 100);
                $subtotal += $lineHt;
                $tax += $lineTax;
            }
            $total = round($subtotal + $tax, 2);
            $paid = $data['payment'] === 'card_stub' ? $total : 0;

            $sale = Sale::query()->create([
                'company_id' => $company->id,
                'store_id' => $storeId,
                'customer_id' => $customer?->id,
                'number' => 'WEB-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'origin' => 'ecommerce',
                'status' => $paid > 0 ? 'confirmed' : 'confirmed',
                'service_mode' => 'delivery',
                'delivery_address' => $data['address'] ?? null,
                'currency' => $company->currency ?? 'MAD',
                'sold_at' => now()->toDateString(),
                'notes' => 'Commande boutique en ligne ('.$data['payment'].') — '.$data['name'],
                'subtotal_ht' => round($subtotal, 2),
                'tax_total' => round($tax, 2),
                'discount_total' => 0,
                'total_ttc' => $total,
                'amount_paid' => $paid,
                'confirmed_at' => now(),
            ]);

            $i = 0;
            foreach ($cart as $line) {
                $lineHt = $line['price'] * $line['quantity'];
                $lineTax = $lineHt * ($line['tax_rate'] / 100);
                SaleLine::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product_id'],
                    'product_name' => $line['name'],
                    'description' => $line['name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['price'],
                    'tax_rate' => $line['tax_rate'],
                    'line_subtotal' => round($lineHt, 2),
                    'line_tax' => round($lineTax, 2),
                    'line_total' => round($lineHt + $lineTax, 2),
                    'sort_order' => $i++,
                ]);

                $product = Product::query()->find($line['product_id']);
                if ($product?->track_stock && $storeId) {
                    $stock->applyMovement([
                        'store_id' => $storeId,
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $line['quantity'],
                        'reference' => $sale->number,
                        'comment' => 'Commande e-commerce',
                        'moved_at' => now(),
                    ]);
                }
            }

            return $sale;
        });

        session()->forget($this->cartKey($company->id));

        return redirect()->route('shop.thanks', [$shopSlug, $sale->id])
            ->with('success', 'Commande enregistrée.');
    }

    public function thanks(string $shopSlug, Sale $sale): View
    {
        $company = $this->company($shopSlug);
        abort_unless($sale->company_id === $company->id, 404);

        return view('shop.thanks', compact('company', 'sale'));
    }

    protected function company(string $shopSlug): Company
    {
        return Company::query()
            ->where('shop_slug', $shopSlug)
            ->where('shop_enabled', true)
            ->firstOrFail();
    }

    protected function cartKey(int $companyId): string
    {
        return 'shop_cart_'.$companyId;
    }
}
