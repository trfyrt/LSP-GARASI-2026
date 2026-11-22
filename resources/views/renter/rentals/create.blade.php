@extends('layouts.app')

@section('title', 'Sewa Baru')

@section('content')
    @php
        $input = 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500';
        $vehicleLabels = ['BIKE' => 'Sepeda', 'MOTORCYCLE' => 'Sepeda Motor', 'CAR' => 'Mobil Bensin', 'EV' => 'Mobil Listrik'];
    @endphp

    <h1 class="text-2xl font-semibold text-slate-900">Sewa Garasi Baru</h1>
    <p class="mt-1 text-sm text-slate-500">
        Tanggal mulai bawaan adalah hari ini. Anda juga dapat memesan di muka sampai {{ $maxStart->translatedFormat('d F Y') }}.
        Daftar garasi menampilkan garasi yang kosong selama seluruh periode sewa.
    </p>

    @if ($errors->has('rental'))
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            {{ $errors->first('rental') }}
        </div>
    @endif

    <form method="POST" action="{{ route('rentals.store') }}" enctype="multipart/form-data" novalidate
        class="mt-6 space-y-6">
        @csrf

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-medium text-slate-900">Data kendaraan</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="vehicle_type" class="mb-1 block text-sm font-medium">Tipe kendaraan</label>
                    <select id="vehicle_type" name="vehicle_type" required class="{{ $input }}">
                        <option value="">Pilih tipe kendaraan</option>
                        @foreach ($vehicleTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('vehicle_type') === $type->value)>{{ $vehicleLabels[$type->value] }}</option>
                        @endforeach
                    </select>
                    @error('vehicle_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="plate-field">
                    <label for="plate_number" class="mb-1 block text-sm font-medium">Nomor plat</label>
                    <input id="plate_number" name="plate_number" type="text" maxlength="12" value="{{ old('plate_number') }}"
                        class="{{ $input }}">
                    @error('plate_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="brand_model" class="mb-1 block text-sm font-medium">Merek &amp; tipe</label>
                    <input id="brand_model" name="brand_model" type="text" maxlength="255" value="{{ old('brand_model') }}" required
                        class="{{ $input }}">
                    @error('brand_model')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="battery-field">
                    <label for="battery_kwh" class="mb-1 block text-sm font-medium">Kapasitas listrik (kWh)</label>
                    <input id="battery_kwh" name="battery_kwh" type="number" step="0.01" min="0.1" value="{{ old('battery_kwh') }}"
                        class="{{ $input }}">
                    @error('battery_kwh')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-medium text-slate-900">Detail sewa</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="start_date" class="mb-1 block text-sm font-medium">Tanggal mulai</label>
                    <input id="start_date" name="start_date" type="date" required
                        min="{{ $today->toDateString() }}" max="{{ $maxStart->toDateString() }}"
                        value="{{ old('start_date', $today->toDateString()) }}" class="{{ $input }}">
                    @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="rental_type" class="mb-1 block text-sm font-medium">Tipe sewa</label>
                    <select id="rental_type" name="rental_type" required class="{{ $input }}">
                        <option value="DAILY" @selected(old('rental_type', 'DAILY') === 'DAILY')>Harian</option>
                        <option value="MONTHLY" @selected(old('rental_type') === 'MONTHLY')>Bulanan</option>
                    </select>
                    @error('rental_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div id="days-field">
                    <label for="days" class="mb-1 block text-sm font-medium">Jumlah hari (maks. <span id="max-daily">-</span>)</label>
                    <input id="days" name="days" type="number" min="1" max="30" value="{{ old('days') }}"
                        class="{{ $input }}">
                    @error('days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="garage_type" class="mb-1 block text-sm font-medium">Tipe garasi</label>
                    <select id="garage_type" class="{{ $input }}"></select>
                    <p id="garage-type-hint" class="mt-1 text-xs text-slate-500"></p>
                </div>

                <div class="sm:col-span-2">
                    <label for="id_garage" class="mb-1 block text-sm font-medium">Garasi tersedia pada periode ini</label>
                    <select id="id_garage" name="id_garage" required class="{{ $input }}"></select>
                    <p id="avail-msg" class="mt-1 text-sm text-red-600"></p>
                    @error('id_garage')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <dl id="summary" class="mt-6 grid max-w-md grid-cols-[10rem_1fr] gap-y-1 rounded-md bg-slate-50 p-4 text-sm">
                <dt class="text-slate-500">Periode sewa</dt>
                <dd id="sum-period">-</dd>
                <dt class="text-slate-500">Biaya sewa</dt>
                <dd id="sum-rent">-</dd>
                <dt class="text-slate-500">Biaya isi listrik</dt>
                <dd id="sum-charging">-</dd>
                <dt class="font-medium text-slate-900">Total biaya</dt>
                <dd id="sum-total" class="font-semibold text-slate-900">-</dd>
            </dl>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-medium text-slate-900">Bukti pelunasan</h2>
            <input id="payment_proof" name="payment_proof" type="file" accept="image/png,image/jpeg" required
                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
            <p class="mt-1 text-xs text-slate-500">Gambar JPG/PNG, maksimal 2 MB.</p>
            @error('payment_proof')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </section>

        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Simpan sewa
        </button>
    </form>

    <script>
        (() => {
            const availabilityUrl = @json(route('rentals.availability'));
            const allowed = @json($allowed);
            const typeLabels = @json(collect($garageTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()]));
            const multiplier = {{ $monthlyMultiplier }};
            const oldGarage = @json(old('id_garage'));

            let garages = [];
            let info = { end_date: null, duration_days: null, days_in_month: 30 };
            let requestId = 0;
            let pendingGarage = oldGarage;

            const $ = (id) => document.getElementById(id);
            const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
            const fmt = (iso) => new Date(iso + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });

            function fillGarageTypes() {
                const vehicle = $('vehicle_type').value;
                const types = Object.keys(allowed).filter((t) => allowed[t].includes(vehicle));
                const previous = $('garage_type').value;
                $('garage_type').innerHTML = types.length
                    ? types.map((t) => `<option value="${t}">${typeLabels[t]}</option>`).join('')
                    : '<option value="">Pilih tipe kendaraan dulu</option>';
                if (types.includes(previous)) $('garage_type').value = previous;
                $('garage-type-hint').textContent = vehicle === 'EV'
                    ? 'Mobil Listrik di Garasi Mobil Listrik wajib mengisi daya hingga penuh (biaya listrik dihitung).'
                    : '';
                fillGarages();
            }

            function fillGarages() {
                const type = $('garage_type').value;
                const list = garages.filter((g) => g.type === type);
                $('id_garage').innerHTML = list.length
                    ? list.map((g) => `<option value="${g.id}">${g.code} - ${rupiah(g.daily_price)}/hari</option>`).join('')
                    : '<option value="">Tidak ada garasi tersedia pada periode ini</option>';
                if (pendingGarage && list.some((g) => String(g.id) === String(pendingGarage))) {
                    $('id_garage').value = pendingGarage;
                    pendingGarage = null;
                }
                summarize();
            }

            function toggleFields() {
                const vehicle = $('vehicle_type').value;
                $('plate-field').hidden = vehicle === 'BIKE';
                $('battery-field').hidden = vehicle !== 'EV';
                $('days-field').hidden = $('rental_type').value === 'MONTHLY';
                fillGarageTypes();
            }

            let timer = null;
            function refreshAvailability() {
                clearTimeout(timer);
                timer = setTimeout(async () => {
                    const id = ++requestId;
                    const params = new URLSearchParams({
                        start_date: $('start_date').value,
                        rental_type: $('rental_type').value,
                        days: $('days').value,
                    });
                    try {
                        const response = await fetch(`${availabilityUrl}?${params}`, { headers: { Accept: 'application/json' } });
                        if (id !== requestId || !response.ok) return;
                        const data = await response.json();
                        garages = data.garages || [];
                        info = data;
                        if (data.max_daily) $('max-daily').textContent = data.max_daily;
                        $('days').max = data.max_daily || 30;
                        $('avail-msg').textContent = data.message || '';
                        fillGarages();
                    } catch (e) {
                        $('avail-msg').textContent = 'Gagal memuat daftar garasi. Muat ulang halaman.';
                    }
                }, 250);
            }

            function summarize() {
                const garage = garages.find((g) => String(g.id) === $('id_garage').value);
                const monthly = $('rental_type').value === 'MONTHLY';
                const days = info.duration_days;
                if (!garage || !days || !info.end_date) {
                    ['sum-period', 'sum-rent', 'sum-charging', 'sum-total'].forEach((id) => $(id).textContent = '-');
                    return;
                }
                const rent = monthly ? multiplier * garage.daily_price : garage.daily_price * days;
                const kwh = parseFloat($('battery_kwh').value) || 0;
                const charging = ($('vehicle_type').value === 'EV' && garage.type === 'EV') ? kwh * (garage.charging_price || 0) : 0;
                $('sum-period').textContent = `${fmt(info.start_date)} - ${fmt(info.end_date)} (${days} hari)`;
                $('sum-rent').textContent = rupiah(rent);
                $('sum-charging').textContent = charging ? rupiah(charging) : '-';
                $('sum-total').textContent = rupiah(rent + charging);
            }

            $('vehicle_type').addEventListener('change', toggleFields);
            $('rental_type').addEventListener('change', () => { toggleFields(); refreshAvailability(); });
            $('start_date').addEventListener('change', refreshAvailability);
            $('days').addEventListener('input', refreshAvailability);
            $('garage_type').addEventListener('change', fillGarages);
            $('id_garage').addEventListener('input', summarize);
            $('battery_kwh').addEventListener('input', summarize);

            toggleFields();
            refreshAvailability();
        })();
    </script>
@endsection
