<?php

namespace App\Filament\Resources\PembayaranPembelians\Tables;

use App\Filament\Resources\PembayaranPembelians\PembayaranPembelianResource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

// ✅ Filament v4 Actions (bukan Filament\Tables\Actions\...)
use Filament\Actions\Action;

class PembayaranPembeliansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_pembayaran')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('vendor.nama_vendor')
                    ->label('Vendor')
                    ->searchable(),

                TextColumn::make('bank')
                    ->label('Bank'),

                TextColumn::make('nilai_pembayaran')
                    ->label('Nilai Pembayaran')
                    ->formatStateUsing(fn ($state) =>
                        'Rp ' . number_format((float) ($state ?? 0), 0, ',', '.')
                    )
                    ->sortable(),
            ])
            ->filters([
                Filter::make('tanggal_pembayaran')
                    ->form([
                        DatePicker::make('start')->label('Dari'),
                        DatePicker::make('end')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['start'] ?? null,
                                fn ($q, $date) => $q->whereDate('tanggal_pembayaran', '>=', $date)
                            )
                            ->when(
                                $data['end'] ?? null,
                                fn ($q, $date) => $q->whereDate('tanggal_pembayaran', '<=', $date)
                            );
                    }),
            ])
            // ✅ HANYA BISA LIHAT (tidak ada Ubah/Hapus)
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => PembayaranPembelianResource::getUrl('view', ['record' => $record])),
            ])
            // ✅ Tidak ada bulk action
            ->toolbarActions([]);
    }
}
