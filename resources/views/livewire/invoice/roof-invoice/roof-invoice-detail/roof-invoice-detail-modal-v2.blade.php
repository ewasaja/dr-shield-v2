@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;
    use App\Models\System\Category;
@endphp
<div class="modal fade modal-lg" id="modal-v2" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Form Detail Pembayaran</h1>
                <button type="button" class="btn-close" wire:click="closeModal()"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="form-label">Jenis <span class="text-danger"></span></div>
                        <select class="form-select @error('category') is-invalid @enderror" id="status" wire:model.live="category" aria-label="Default select example">
                            <option value=""selected style="display: none">-- Pilih Jenis Pembayaran  --</option>
                            <option value="dr-sonne" {{ $category == "dr-sonne" ? "selected" : "" }}>Dr Sonne</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <div class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></div>
                        <input type="date" class="form-control @error('invoice_detail_date') is-invalid @enderror" wire:model.live="invoice_detail_date" placeholder="">
                        @error('invoice_detail_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <div class="form-label">Nominal Pembayaran <span class="text-danger">*</span></div>
                        <input type="number" class="form-control @error('invoice_detail_amount') is-invalid @enderror" wire:model.live="invoice_detail_amount" placeholder="Contoh : 2000000">
                        @error('invoice_detail_amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <div class="form-label">Persentase <span class="text-danger">*</span></div>
                        <input type="number" class="form-control @error('percentage') is-invalid @enderror" wire:model.live="percentage" placeholder="Contoh : 100" disabled>
                        @error('percentage')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModal()">Tutup <i class="fa-solid fa-circle-xmark fa-fw ms-2"></i></button>
                <button type="button" class="btn btn-success btn-sm" wire:click="saveData()">Simpan <i class="fa-solid fa-circle-check fa-fw ms-2"></i></button>
            </div>
        </div>
    </div>
</div>