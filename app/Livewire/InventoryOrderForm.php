<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class InventoryOrderForm extends Component
{
    // Propiedades para la orden
    public $orderId = null;
    public $orderNumber = '';
    public $reference = '';
    public $clientName = '';
    public $clientEmail = '';
    public $clientPhone = '';
    public $notes = '';
    public $status = 'draft';

    // Propiedades para la búsqueda de productos
    public $search = '';
    public $searchResults = [];
    public $selectedItem = null;
    public $loading = false;

    // Propiedades para el carrito/items
    public $cartItems = [];
    public $quantity = 1;

    protected $rules = [
        'clientName' => 'required|string|max:255',
        'clientEmail' => 'nullable|email|max:255',
        'clientPhone' => 'nullable|string|max:20',
        'reference' => 'nullable|string|max:255',
        'notes' => 'nullable|string',
        'cartItems' => 'required|array|min:1',
        'cartItems.*.requested_quantity' => 'required|numeric|min:0.01',
    ];

    public function mount($orderId = null)
    {
        if ($orderId) {
            $this->loadOrder($orderId);
        } else {
            $this->orderNumber = InventoryOrder::generateOrderNumber();
        }
    }

    public function loadOrder($orderId)
    {
        $order = InventoryOrder::with('items')->findOrFail($orderId);

        // Cargar datos principales
        $this->orderId = $order->id;
        $this->orderNumber = $order->order_number;
        $this->reference = $order->reference;
        $this->clientName = $order->client_name;
        $this->clientEmail = $order->client_email;
        $this->clientPhone = $order->client_phone;
        $this->notes = $order->notes;
        $this->status = $order->status;

        // Cargar items al carrito
        $this->cartItems = [];
        foreach ($order->items as $item) {
            $this->cartItems[] = [
                'id' => $item->id,
                'product_code' => $item->product_code,
                'zeta_code' => $item->zeta_code,
                'description' => $item->description,
                'requested_quantity' => $item->requested_quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'product_data' => json_decode($item->product_data, true),
            ];
        }
    }

    public function searchProducts()
    {
        $this->validate([
            'search' => 'required|min:3',
        ]);

        $this->loading = true;
        $this->searchResults = [];

        try {
            $params = [
                'cod_art' => $this->search,
                'cod_bod' => '01',
            ];

            $response = Http::timeout(15)
                ->withOptions(['verify' => false])
                ->get(env('API_SALDOS_URL'), $params);

            if ($response->successful()) {
                $data = $response->json();
                $this->searchResults = $data['data'] ?? [];

                if (empty($this->searchResults)) {
                    // Intentar búsqueda por zeta
                    $params = [
                        'codigo' => $this->search,
                        'cod_bod' => '01',
                    ];

                    $response = Http::get(env('API_SALDOS_URL'), $params);
                    if ($response->successful()) {
                        $data = $response->json();
                        $this->searchResults = $data['data'] ?? [];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al buscar productos: ' . $e->getMessage());
        }

        $this->loading = false;
    }

    public function selectItem($index)
    {
        $this->selectedItem = $this->searchResults[$index];
        $this->quantity = 1;
    }

    public function addToCart()
    {
        if (!$this->selectedItem) {
            return;
        }

        $this->validate([
            'quantity' => 'required|numeric|min:0.01|max:' . $this->selectedItem['Saldo_Disponible'],
        ]);

        // Verificar si ya existe en el carrito
        $existingIndex = collect($this->cartItems)->search(function ($item) {
            return $item['product_code'] === $this->selectedItem['Código_Artículo'] &&
                   $item['zeta_code'] === $this->selectedItem['Zeta_Articulo'];
        });

        if ($existingIndex !== false) {
            // Actualizar cantidad
            $this->cartItems[$existingIndex]['requested_quantity'] += $this->quantity;
            $this->cartItems[$existingIndex]['total_price'] =
                $this->cartItems[$existingIndex]['requested_quantity'] *
                $this->cartItems[$existingIndex]['unit_price'];
        } else {
            // Agregar nuevo item
            $this->cartItems[] = [
                'id' => null,
                'product_code' => $this->selectedItem['Código_Artículo'],
                'zeta_code' => $this->selectedItem['Zeta_Articulo'],
                'description' => $this->selectedItem['Descripción_Artículo'],
                'requested_quantity' => $this->quantity,
                'unit_price' => $this->selectedItem['Costo'],
                'total_price' => $this->quantity * $this->selectedItem['Costo'],
                'product_data' => json_encode($this->selectedItem),
            ];
        }

        $this->selectedItem = null;
        $this->quantity = 1;
    }

    public function removeItem($index)
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
    }

    public function updateQuantity($index, $quantity)
    {
        $this->cartItems[$index]['requested_quantity'] = $quantity;
        $this->cartItems[$index]['total_price'] = $quantity * $this->cartItems[$index]['unit_price'];
    }

    public function saveAsDraft()
    {
        $this->status = 'draft';
        $this->save();
    }

    public function savePending()
    {
        $this->status = 'pending';
        $this->save();
    }

    public function save()
    {
        $this->validate();

        try {
            // Iniciar transacción
            \DB::beginTransaction();

            // Crear o actualizar la orden
            $orderData = [
                'store_id' => Filament::getTenant()->id,
                'order_number' => $this->orderNumber,
                'reference' => $this->reference,
                'client_name' => $this->clientName,
                'client_email' => $this->clientEmail,
                'client_phone' => $this->clientPhone,
                'notes' => $this->notes,
                'status' => $this->status,
                'last_modified_by' => auth()->user()->name,
            ];

            if ($this->orderId) {
                $order = InventoryOrder::findOrFail($this->orderId);
                $order->update($orderData);
            } else {
                $orderData['created_by'] = auth()->user()->name;
                $order = InventoryOrder::create($orderData);
                $this->orderId = $order->id;
            }

            // Actualizar o crear items
            $currentItemIds = [];

            foreach ($this->cartItems as $item) {
                $itemData = [
                    'product_code' => $item['product_code'],
                    'zeta_code' => $item['zeta_code'],
                    'description' => $item['description'],
                    'requested_quantity' => $item['requested_quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'product_data' => is_string($item['product_data']) ? $item['product_data'] : json_encode($item['product_data']),
                ];

                if (isset($item['id']) && $item['id']) {
                    // Actualizar item existente
                    $orderItem = InventoryOrderItem::findOrFail($item['id']);
                    $orderItem->update($itemData);
                    $currentItemIds[] = $orderItem->id;
                } else {
                    // Crear nuevo item
                    $orderItem = $order->items()->create($itemData);
                    $currentItemIds[] = $orderItem->id;
                }
            }

            // Eliminar items que ya no están en el carrito
            $order->items()->whereNotIn('id', $currentItemIds)->delete();

            \DB::commit();

            $statusText = $this->status === 'draft' ? 'borrador' : 'pendiente';

            Notification::make()
                ->title('Pedido guardado')
                ->body("El pedido ha sido guardado como {$statusText} correctamente.")
                ->success()
                ->send();

            if ($this->status !== 'draft') {
                // Redirigir a la vista de recursos de Filament directamente
                return redirect()->route('filament.admin.resources.inventory-orders.index', [
                    'tenant' => Filament::getTenant()->id
                ]);
            } else {
                // Recargar el formulario con la nueva info
                $this->loadOrder($this->orderId);
            }

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Error al guardar pedido: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->body('Ocurrió un error al guardar el pedido: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.inventory-order-form');
    }
}
