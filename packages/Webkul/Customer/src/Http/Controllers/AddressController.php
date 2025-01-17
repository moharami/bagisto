<?php

namespace Webkul\Customer\Http\Controllers;

use Webkul\Customer\Http\Requests\CustomerAddressRequest;
use Webkul\Customer\Repositories\CustomerAddressRepository;

class AddressController extends Controller
{
    /**
     * Contains route related configuration.
     *
     * @var array
     */
    protected $_config;

    /**
     * Current customer.
     *
     * @var \Webkul\Customer\Models\Customer
     */
    protected $customer;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Customer\Repositories\CustomerAddressRepository  $customerAddressRepository
     * @return void
     */
    public function __construct(protected CustomerAddressRepository $customerAddressRepository)
    {
        $this->_config = request('_config');
    }

    /**
     * Address route index page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $customer = auth()->guard('customer')->user();

        return view($this->_config['view'])->with('addresses', $customer->addresses);
    }

    /**
     * Show the address create form.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view($this->_config['view'], [
            'defaultCountry' => config('app.default_country'),
        ]);
    }

    /**
     * Create a new address for customer.
     *
     * @return view
     */
    public function store(CustomerAddressRequest $request)
    {
        $customer = auth()->guard('customer')->user();

        $data = $request->all();

        $data['customer_id'] = $customer->id;
        $data['address1'] = implode(PHP_EOL, array_filter(request()->input('address1')));

        if ($customer->addresses->count() == 0) {
            $data['default_address'] = 1;
        }

        if ($this->customerAddressRepository->create($data)) {
            session()->flash('success', trans('shop::app.customer.account.address.create.success'));

            return redirect()->route($this->_config['redirect']);
        }

        session()->flash('error', trans('shop::app.customer.account.address.create.error'));

        return redirect()->back();
    }

    /**
     * For editing the existing addresses of current logged in customer.
     *
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $customer = auth()->guard('customer')->user();

        $address = $this->customerAddressRepository->findOneWhere([
            'id'          => $id,
            'customer_id' => $customer->id,
        ]);

        if (! $address) {
            abort(404);
        }

        return view($this->_config['view'], array_merge(compact('address'), [
            'defaultCountry' => config('app.default_country'),
        ]));
    }

    /**
     * Edit's the premade resource of customer called Address.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, CustomerAddressRequest $request)
    {
        $customer = auth()->guard('customer')->user();

        $data = $request->all();

        $data['address1'] = implode(PHP_EOL, array_filter(request()->input('address1')));

        $addresses = $customer->addresses;

        foreach ($addresses as $address) {
            if ($id != $address->id) {
                return;
            }
            session()->flash('success', trans('shop::app.customer.account.address.edit.success'));

            $this->customerAddressRepository->update($data, $id);

            return redirect()->route('customer.address.index');

        }

        session()->flash('warning', trans('shop::app.security-warning'));

        return redirect()->route('customer.address.index');
    }

    /**
     * To change the default address or make the default address,
     * by default when first address is created will be the default address.
     *
     * @return \Illuminate\Http\Response
     */
    public function makeDefault($id)
    {
        $customer = auth()->guard('customer')->user();

        if ($default = $customer->default_address) {
            $this->customerAddressRepository->find($default->id)->update(['default_address' => 0]);
        }

        if ($address = $this->customerAddressRepository->find($id)) {
            $address->update(['default_address' => 1]);
        } else {
            session()->flash('success', trans('shop::app.customer.account.address.index.default-delete'));
        }

        return redirect()->back();
    }

    /**
     * Delete address of the current customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = auth()->guard('customer')->user();

        $address = $this->customerAddressRepository->findOneWhere([
            'id'          => $id,
            'customer_id' => $customer->id,
        ]);

        if (! $address) {
            abort(404);
        }

        $this->customerAddressRepository->delete($id);

        session()->flash('success', trans('shop::app.customer.account.address.delete.success'));

        return redirect()->route('customer.address.index');
    }
}
