@extends('layouts.admin')

@section('content')
    <div class="payment-page">

        <div class="pg-bar"></div>


        {{-- HEADER --}}
        <div class="sec-bar">

            <div>

                <a href="{{ route('admin.payment.pembayaran') }}" class="payment-action" style="margin-bottom:10px;">
                    <span class="material-symbols-rounded">
                        arrow_back
                    </span>

                    Kembali
                </a>

                <div class="sec-hd">
                    Detail Pembayaran
                </div>

                <div class="sec-sub">
                    Informasi transaksi pembayaran dan registrasi terkait
                </div>

            </div>

        </div>


        <div class="payment-detail-grid">

            {{-- KOLOM UTAMA --}}
            <div>

                {{-- INFORMASI PEMBAYARAN --}}
                <div class="payment-detail-card">

                    <div class="payment-detail-header">

                        <div class="payment-detail-title">
                            <span class="material-symbols-rounded">
                                payments
                            </span>

                            Informasi Pembayaran
                        </div>

                        <span id="paymentDetailStatus" class="payment-status success">
                            Berhasil
                        </span>

                    </div>


                    <div class="payment-detail-body">

                        <div class="payment-detail-row">
                            <div class="payment-detail-label">
                                Kode Pembayaran
                            </div>

                            <div class="payment-detail-value mono" id="detailKodeBayar">
                                -
                            </div>
                        </div>


                        <div class="payment-detail-row">
                            <div class="payment-detail-label">
                                Metode Pembayaran
                            </div>

                            <div class="payment-detail-value" id="detailMetode">
                                -
                            </div>
                        </div>


                        <div class="payment-detail-row">
                            <div class="payment-detail-label">
                                Status
                            </div>

                            <div class="payment-detail-value" id="detailStatus">
                                -
                            </div>
                        </div>


                        <div class="payment-detail-row">
                            <div class="payment-detail-label">
                                Waktu Kedaluwarsa
                            </div>

                            <div class="payment-detail-value" id="detailExpired">
                                -
                            </div>
                        </div>


                        <div class="payment-detail-row">
                            <div class="payment-detail-label">
                                Payment Gateway Ref
                            </div>

                            <div class="payment-detail-value mono" id="detailGatewayRef">
                                -
                            </div>
                        </div>

                    </div>

                </div>


                {{-- REGISTRASI --}}
                <div class="payment-detail-card">

                    <div class="payment-detail-header">

                        <div class="payment-detail-title">

                            <span class="material-symbols-rounded">
                                confirmation_number
                            </span>

                            Registrasi Terkait

                        </div>

                    </div>


                    <div class="payment-detail-body">

                        <div class="payment-related-box">

                            <div class="payment-related-main">

                                <div class="payment-related-code" id="detailRegistration">
                                    REG-001
                                </div>

                                <div class="payment-related-name" id="detailVisitor">
                                    Budi Santoso
                                </div>

                                <div class="payment-related-meta" id="detailRegistrationType">
                                    Retribusi
                                </div>

                            </div>


                            <a href="#" class="payment-related-link" id="detailRegistrationLink">
                                Lihat Pemesanan
                            </a>

                        </div>

                    </div>

                </div>

            </div>


            {{-- SIDEBAR --}}
            <div>

                <div class="payment-detail-card">

                    <div class="payment-detail-header">

                        <div class="payment-detail-title">

                            <span class="material-symbols-rounded">
                                info
                            </span>

                            Informasi

                        </div>

                    </div>


                    <div class="payment-detail-body">

                        <div class="payment-info-notice">

                            <span class="material-symbols-rounded">
                                info
                            </span>

                            <div>
                                Status pembayaran diperoleh dari
                                payment gateway melalui callback/webhook.
                                Admin tidak melakukan verifikasi pembayaran
                                secara manual.
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
@endsection
