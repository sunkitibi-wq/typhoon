import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login, register } from '@/routes';
import { useState } from 'react';

interface Rate {
    id: number;
    base_currency: string;
    quote_currency: string;
    bid: number;
    ask: number;
    mid_rate: number;
    change_24h: number;
    volume_24h: number;
    last_refreshed_at: string;
}

export default function Welcome({
    canRegister = true,
    rates = [],
}: {
    canRegister?: boolean;
    rates?: Rate[];
}) {
    const { auth } = usePage().props;
    const [activeTab, setActiveTab] = useState<'fiat' | 'crypto'>('crypto');

    // Group rates safely
    const cryptoRates = rates.filter(r => ['BTC', 'ETH', 'SOL', 'USDT', 'XRP'].includes(r.base_currency));

    return (
        <>
            <Head title="Typhoon Banking - Cryptographic Wealth Hub" />
            
            <div className="min-h-screen bg-slate-50 text-slate-900 selection:bg-teal-500 selection:text-white dark:bg-slate-950 dark:text-slate-100 transition-colors duration-300">
                {/* Header */}
                <header className="sticky top-0 z-50 backdrop-blur-md bg-slate-50/80 border-b border-slate-200/80 dark:bg-slate-950/80 dark:border-slate-900/80">
                    <div className="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            {/* Logo Icon */}
                            <div className="h-10 w-10 rounded-xl bg-gradient-to-tr from-teal-500 via-emerald-400 to-indigo-600 flex items-center justify-center shadow-md shadow-teal-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2.5" stroke="currentColor" className="w-5 h-5 text-white animate-pulse">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </div>
                            <span className="text-xl font-bold tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 dark:from-white dark:to-slate-300 bg-clip-text text-transparent">
                                Typhoon <span className="bg-gradient-to-r from-teal-500 to-indigo-500 bg-clip-text text-transparent">Banking</span>
                            </span>
                        </div>

                        <nav className="flex items-center gap-6">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-teal-600 to-indigo-600 hover:from-teal-500 hover:to-indigo-500 shadow-md shadow-teal-500/10 hover:shadow-teal-500/20 transform hover:-translate-y-0.5 transition-all"
                                >
                                    Go to Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="text-sm font-medium hover:text-teal-500 transition-colors"
                                    >
                                        Log in
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white shadow-sm transform hover:-translate-y-0.5 transition-all"
                                        >
                                            Get Started
                                        </Link>
                                    )}
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="relative overflow-hidden pt-12 pb-24 lg:pt-20 lg:pb-32">
                    {/* Background Gradients */}
                    <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-teal-500/10 blur-[120px] rounded-full pointer-events-none"></div>
                    <div className="absolute top-1/3 left-1/3 w-[350px] h-[350px] bg-indigo-500/10 blur-[100px] rounded-full pointer-events-none"></div>

                    <div className="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                        {/* Copy Block */}
                        <div className="lg:col-span-7 space-y-8 text-center lg:text-left">
                            <div className="inline-flex items-center gap-2.5 px-4 py-2 rounded-full border border-teal-500/30 bg-teal-500/5 text-teal-600 dark:text-teal-400 text-xs font-semibold uppercase tracking-wider">
                                <span className="h-2 w-2 rounded-full bg-teal-500 animate-ping"></span>
                                Institutional Cryptographic Core Enabled
                            </div>
                            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-none text-slate-900 dark:text-white">
                                Next-Gen Banking with{' '}
                                <span className="bg-gradient-to-r from-teal-500 via-emerald-400 to-indigo-600 bg-clip-text text-transparent">
                                    Absolute Integrity
                                </span>
                            </h1>
                            <p className="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                                Experience a complete fiat and digital asset ecosystem. Powered by Typhoon's double-ledger security with row-level transaction safety, instant multi-token exchange rates, and secure private KYC storage.
                            </p>

                            <div className="flex flex-wrap items-center justify-center lg:justify-start gap-4">
                                <Link
                                    href={register()}
                                    className="px-8 py-4 rounded-2xl text-base font-bold text-white bg-gradient-to-r from-teal-500 via-emerald-400 to-indigo-600 hover:opacity-95 shadow-lg shadow-teal-500/20 transform hover:-translate-y-0.5 transition-all"
                                >
                                    Open Free Account
                                </Link>
                                <a
                                    href="#rates-section"
                                    className="px-8 py-4 rounded-2xl text-base font-semibold border border-slate-200 hover:bg-slate-100 dark:border-slate-800 dark:hover:bg-slate-900 transition-all"
                                >
                                    View Live Rates
                                </a>
                            </div>

                            {/* Trust KPI */}
                            <div className="pt-8 border-t border-slate-200 dark:border-slate-900 grid grid-cols-3 gap-6 max-w-md mx-auto lg:mx-0 text-center lg:text-left">
                                <div>
                                    <p className="text-2xl font-extrabold text-teal-500">0ms</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Race Conditions</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-extrabold text-indigo-500">SEPA</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Instant Clearing</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-extrabold text-teal-500">256-bit</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Vault Security</p>
                                </div>
                            </div>
                        </div>

                        {/* Graphic / Rates Card */}
                        <div id="rates-section" className="lg:col-span-5 relative w-full">
                            <div className="relative rounded-3xl border border-slate-200/80 bg-white/50 p-6 shadow-xl backdrop-blur-md dark:border-slate-900/80 dark:bg-slate-900/50">
                                <div className="flex items-center justify-between mb-6">
                                    <h3 className="font-bold text-lg">Market Exchange Rates</h3>
                                    <span className="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
                                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Live Feed
                                    </span>
                                </div>

                                {/* Custom Tab */}
                                <div className="flex bg-slate-100 dark:bg-slate-950 p-1 rounded-xl mb-6">
                                    <button
                                        onClick={() => setActiveTab('crypto')}
                                        className={`flex-1 py-2 text-xs font-bold rounded-lg transition-all ${activeTab === 'crypto' ? 'bg-white shadow dark:bg-slate-900 text-teal-500' : 'text-slate-500'}`}
                                    >
                                        Cryptocurrency (EUR)
                                    </button>
                                    <button
                                        onClick={() => setActiveTab('fiat')}
                                        className={`flex-1 py-2 text-xs font-bold rounded-lg transition-all ${activeTab === 'fiat' ? 'bg-white shadow dark:bg-slate-900 text-teal-500' : 'text-slate-500'}`}
                                    >
                                        Global Fiat
                                    </button>
                                </div>

                                {activeTab === 'crypto' ? (
                                    <div className="space-y-4">
                                        {cryptoRates.length > 0 ? (
                                            cryptoRates.map(rate => (
                                                <div key={rate.id} className="flex items-center justify-between p-3 rounded-xl hover:bg-slate-100/50 dark:hover:bg-slate-900/50 transition-colors">
                                                    <div className="flex items-center gap-3">
                                                        <div className="h-8 w-8 rounded-lg bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold text-xs">
                                                            {rate.base_currency}
                                                        </div>
                                                        <div>
                                                            <p className="text-xs font-bold">{rate.base_currency} / {rate.quote_currency}</p>
                                                            <p className="text-[10px] text-slate-500">Refreshed just now</p>
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-sm font-extrabold">
                                                            {rate.mid_rate >= 1 
                                                                ? rate.mid_rate.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) 
                                                                : rate.mid_rate.toFixed(5)} €
                                                        </p>
                                                        <p className={`text-xs font-semibold ${Number(rate.change_24h) >= 0 ? 'text-emerald-500' : 'text-rose-500'}`}>
                                                            {Number(rate.change_24h) >= 0 ? '+' : ''}{Number(rate.change_24h).toFixed(2)}%
                                                        </p>
                                                    </div>
                                                </div>
                                            ))
                                        ) : (
                                            <div className="py-12 text-center text-slate-500 text-sm">
                                                No active rates found. Run rates command to sync.
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {[
                                            { pair: 'USD / EUR', price: '0.92 €', change: 0.05 },
                                            { pair: 'GBP / EUR', price: '1.17 €', change: -0.12 },
                                            { pair: 'CHF / EUR', price: '1.02 €', change: 0.22 },
                                            { pair: 'JPY / EUR', price: '0.0059 €', change: -0.45 },
                                        ].map((mock, idx) => (
                                            <div key={idx} className="flex items-center justify-between p-3 rounded-xl hover:bg-slate-100/50 dark:hover:bg-slate-900/50 transition-colors">
                                                <div className="flex items-center gap-3">
                                                    <div className="h-8 w-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                                                        {mock.pair.split(' ')[0]}
                                                    </div>
                                                    <div>
                                                        <p className="text-xs font-bold">{mock.pair}</p>
                                                        <p className="text-[10px] text-slate-500">Real-time interbank rate</p>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-extrabold">{mock.price}</p>
                                                    <p className={`text-xs font-semibold ${mock.change >= 0 ? 'text-emerald-500' : 'text-rose-500'}`}>
                                                        {mock.change >= 0 ? '+' : ''}{mock.change.toFixed(2)}%
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* Core Features */}
                <section className="py-24 border-t border-slate-200 dark:border-slate-900 relative">
                    <div className="max-w-7xl mx-auto px-6">
                        <div className="text-center max-w-3xl mx-auto space-y-4 mb-20">
                            <h2 className="text-xs font-bold uppercase tracking-widest text-teal-500">Engineered for Perfection</h2>
                            <p className="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                                Cryptographic Integrity & Premium Fintech Features
                            </p>
                            <p className="text-slate-600 dark:text-slate-400 leading-relaxed text-base">
                                Experience a highly resilient application where operations execution logic has been built for maximum security, accuracy, and regulatory alignment.
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {/* Card 1 */}
                            <div className="p-8 rounded-3xl border border-slate-200/50 bg-white/40 dark:border-slate-900/50 dark:bg-slate-900/40 hover:border-teal-500/40 hover:-translate-y-1 transition-all group">
                                <div className="h-12 w-12 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-6 h-6">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </div>
                                <h3 className="font-bold text-lg mb-3">Row-Level Security Locks</h3>
                                <p className="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                                    Zero-race-condition guarantees. Transfers, deposits, and withdrawal transactions are secured via deterministic row-level locks, eliminating transaction balance bugs.
                                </p>
                            </div>

                            {/* Card 2 */}
                            <div className="p-8 rounded-3xl border border-slate-200/50 bg-white/40 dark:border-slate-900/50 dark:bg-slate-900/40 hover:border-indigo-500/40 hover:-translate-y-1 transition-all group">
                                <div className="h-12 w-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-6 h-6">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                    </svg>
                                </div>
                                <h3 className="font-bold text-lg mb-3">Multi-Token Crypto Exchange</h3>
                                <p className="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                                    Execute instant market orders for BTC, ETH, SOL, XRP, and USDT. Exchange fiat to crypto and back under dynamic live rate calculations with full balance protection.
                                </p>
                            </div>

                            {/* Card 3 */}
                            <div className="p-8 rounded-3xl border border-slate-200/50 bg-white/40 dark:border-slate-900/50 dark:bg-slate-900/40 hover:border-teal-500/40 hover:-translate-y-1 transition-all group">
                                <div className="h-12 w-12 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-6 h-6">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" />
                                    </svg>
                                </div>
                                <h3 className="font-bold text-lg mb-3">Structured Auto Loans</h3>
                                <p className="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                                    Underwrite business and personal loans with automated payment scheduling, instant disbursement, and cumulative overdue tracking to enforce safety.
                                </p>
                            </div>

                            {/* Card 4 */}
                            <div className="p-8 rounded-3xl border border-slate-200/50 bg-white/40 dark:border-slate-900/50 dark:bg-slate-900/40 hover:border-indigo-500/40 hover:-translate-y-1 transition-all group">
                                <div className="h-12 w-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-6 h-6">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 21a9.003 9.003 0 008.354-5.646 9.003 9.003 0 00-8.354-5.646 9.003 9.003 0 00-8.354 5.646 9.003 9.003 0 008.354 5.646z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 11V3m0 8l-4-4m4 4l4-4" />
                                    </svg>
                                </div>
                                <h3 className="font-bold text-lg mb-3">Global Routing Channels</h3>
                                <p className="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                                    Seamless SEPA credit transfers, direct debits, and global SWIFT wire transfers. Integrated with external bank ledger fallback paths for external routing.
                                </p>
                            </div>

                            {/* Card 5 */}
                            <div className="p-8 rounded-3xl border border-slate-200/50 bg-white/40 dark:border-slate-900/50 dark:bg-slate-900/40 hover:border-teal-500/40 hover:-translate-y-1 transition-all group">
                                <div className="h-12 w-12 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-6 h-6">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h3.75M9 15h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H9.75M3 16.5V4.875C3 4.254 3.504 3.75 4.125 3.75H8.25m0 0V21m0-17.25h8.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125H8.25M6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0-6h.008v.008H6.75V9z" />
                                    </svg>
                                </div>
                                <h3 className="font-bold text-lg mb-3">Audited KYC Storage Vault</h3>
                                <p className="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                                    Your personal identify verification files are saved inside a secure local storage disk (completely isolated from the public web server) and accessible only to authorized operators.
                                </p>
                            </div>

                            {/* Card 6 */}
                            <div className="p-8 rounded-3xl border border-slate-200/50 bg-white/40 dark:border-slate-900/50 dark:bg-slate-900/40 hover:border-indigo-500/40 hover:-translate-y-1 transition-all group">
                                <div className="h-12 w-12 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="2" stroke="currentColor" className="w-6 h-6">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                                    </svg>
                                </div>
                                <h3 className="font-bold text-lg mb-3">Enterprise Financial Reporting</h3>
                                <p className="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                                    Track capital growth, generate instant transaction statements per account, schedule standing orders, and monitor crypto/fiat profit analysis dynamically.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Final CTA */}
                <section className="py-24 border-t border-slate-200 dark:border-slate-900 relative">
                    <div className="absolute inset-0 bg-gradient-to-b from-transparent via-teal-500/5 to-transparent pointer-events-none"></div>
                    <div className="max-w-5xl mx-auto px-6 relative text-center space-y-8">
                        <h2 className="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                            Take Command of Your Wealth <br />
                            <span className="bg-gradient-to-r from-teal-500 to-indigo-500 bg-clip-text text-transparent">With Zero Compromises</span>
                        </h2>
                        <p className="text-slate-600 dark:text-slate-400 text-base sm:text-lg max-w-2xl mx-auto">
                            Join the modern era of financial operations. Experience absolute security, seamless fiat-crypto liquidity, and institutional compliance standards.
                        </p>
                        <div className="flex flex-wrap items-center justify-center gap-4 pt-4">
                            <Link
                                href={register()}
                                className="px-8 py-4 rounded-2xl text-base font-bold text-white bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white shadow-lg transform hover:-translate-y-0.5 transition-all"
                            >
                                Get Started Instantly
                            </Link>
                            <Link
                                href={login()}
                                className="px-8 py-4 rounded-2xl text-base font-bold border border-slate-200 hover:bg-slate-100 dark:border-slate-800 dark:hover:bg-slate-900 transition-all"
                            >
                                Log into Account
                            </Link>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-slate-200 bg-slate-100 dark:border-slate-900 dark:bg-slate-950/40 py-12">
                    <div className="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6 text-slate-500 text-sm">
                        <div className="flex items-center gap-3">
                            <div className="h-6 w-6 rounded-lg bg-teal-500 flex items-center justify-center text-white font-bold text-xs">
                                T
                            </div>
                            <span className="font-semibold text-slate-800 dark:text-slate-300">Typhoon Banking</span>
                        </div>
                        <p>&copy; {new Date().getFullYear()} Typhoon Banking. All rights reserved. Built with cryptographic ledger protection.</p>
                        <div className="flex gap-6">
                            <a href="#" className="hover:text-teal-500 transition-colors">Privacy Policy</a>
                            <a href="#" className="hover:text-teal-500 transition-colors">Terms of Service</a>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
