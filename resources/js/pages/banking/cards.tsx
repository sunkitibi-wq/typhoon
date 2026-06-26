import { Head, useForm } from '@inertiajs/react';
import { CreditCard, Plus, Shield, ShieldAlert, Key, Ban, Eye, EyeOff } from 'lucide-react';
import { Card as CardContainer, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useState } from 'react';

interface Account {
    id: number;
    label: string;
    account_number: string;
    currency: string;
    balance: number;
    solaris_account_id: string | null;
}

interface CardModel {
    id: number;
    user_id: number;
    account_id: number;
    solaris_card_id: string | null;
    type: string;
    cardholder_name: string;
    masked_pan: string;
    expiration_date: string;
    status: string;
    created_at: string;
}

export default function Cards({ cards, accounts }: { cards: CardModel[]; accounts: Account[] }) {
    const createForm = useForm({
        account_id: '',
        type: 'virtual',
        cardholder_name: '',
    });

    const [selectedCardForPin, setSelectedCardForPin] = useState<CardModel | null>(null);
    const pinForm = useForm({
        pin: '',
    });

    const [showPanId, setShowPanId] = useState<number | null>(null);

    const handleCreateCard = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('banking.cards.store'), {
            onSuccess: () => createForm.reset('cardholder_name'),
        });
    };

    const handleToggleStatus = (card: CardModel, newStatus: string) => {
        if (confirm(`Are you sure you want to change this card status to ${newStatus}?`)) {
            const form = useForm({ status: newStatus });
            form.post(route('banking.cards.toggle', card.id));
        }
    };

    const handleSetPin = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedCardForPin) return;

        pinForm.post(route('banking.cards.pin', selectedCardForPin.id), {
            onSuccess: () => {
                setSelectedCardForPin(null);
                pinForm.reset();
            },
        });
    };

    return (
        <>
            <Head title="Cards Management" />
            <div className="flex flex-col gap-6 p-6 max-w-7xl mx-auto">
                <div>
                    <h1 className="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-500 to-cyan-500 bg-clip-text text-transparent">
                        Cards Management
                    </h1>
                    <p className="text-muted-foreground mt-1">
                        Create, configure, and monitor your virtual and physical cards.
                    </p>
                </div>

                <div className="grid gap-8 lg:grid-cols-3">
                    {/* Create Card Form */}
                    <div className="lg:col-span-1 space-y-6">
                        <CardContainer className="border-border/50 shadow-lg backdrop-blur-md bg-card/60">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Plus className="h-5 w-5 text-indigo-500" />
                                    Order New Card
                                </CardTitle>
                                <CardDescription>
                                    Issue a virtual or physical card linked to your account.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleCreateCard} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="account_id">Link to Account *</Label>
                                        <Select
                                            value={createForm.data.account_id}
                                            onValueChange={(v) => createForm.setData('account_id', v)}
                                        >
                                            <SelectTrigger id="account_id" className="w-full">
                                                <SelectValue placeholder="Select active account" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {accounts.map((a) => (
                                                    <SelectItem key={a.id} value={String(a.id)}>
                                                        {a.label} ({a.account_number}) — {a.currency} {Number(a.balance).toFixed(2)}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={createForm.errors.account_id} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="type">Card Type *</Label>
                                        <Select
                                            value={createForm.data.type}
                                            onValueChange={(v) => createForm.setData('type', v)}
                                        >
                                            <SelectTrigger id="type" className="w-full">
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="virtual">Virtual Card (Instant)</SelectItem>
                                                <SelectItem value="physical">Physical Card (Shipped)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={createForm.errors.type} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="cardholder_name">Cardholder Name (Optional)</Label>
                                        <Input
                                            id="cardholder_name"
                                            value={createForm.data.cardholder_name}
                                            onChange={(e) => createForm.setData('cardholder_name', e.target.value)}
                                            placeholder="Leave blank to use account name"
                                        />
                                        <InputError message={createForm.errors.cardholder_name} />
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={createForm.processing}
                                        className="w-full bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 shadow-md font-semibold text-white"
                                    >
                                        Order Card
                                    </Button>
                                </form>
                            </CardContent>
                        </CardContainer>
                    </div>

                    {/* Cards Display List */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="flex justify-between items-center">
                            <h2 className="text-xl font-bold text-foreground">Your Active Cards</h2>
                            <Badge variant="outline" className="px-3 py-1 font-semibold text-sm">
                                {cards.length} Card{cards.length !== 1 ? 's' : ''}
                            </Badge>
                        </div>

                        {cards.length === 0 ? (
                            <div className="flex flex-col items-center justify-center border-2 border-dashed border-border/50 rounded-2xl p-12 text-center bg-card/20">
                                <CreditCard className="h-12 w-12 text-muted-foreground mb-4 opacity-50" />
                                <h3 className="font-semibold text-lg text-foreground">No cards issued</h3>
                                <p className="text-muted-foreground max-w-sm mt-1">
                                    You don't have any virtual or physical cards. Use the ordering wizard to issue your first card instantly.
                                </p>
                            </div>
                        ) : (
                            <div className="grid gap-6 sm:grid-cols-2">
                                {cards.map((card) => {
                                    const isBlocked = card.status === 'blocked';
                                    const isClosed = card.status === 'closed';
                                    const account = accounts.find((a) => a.id === card.account_id);

                                    return (
                                        <div
                                            key={card.id}
                                            className="group flex flex-col gap-4 border border-border/40 rounded-2xl p-5 shadow-md bg-card/40 hover:shadow-xl hover:border-indigo-500/30 transition-all duration-300 relative overflow-hidden"
                                        >
                                            {/* Beautiful CSS Credit Card Graphic */}
                                            <div
                                                className={`h-48 w-full rounded-xl p-5 flex flex-col justify-between text-white shadow-lg transition-transform duration-300 group-hover:scale-[1.02] relative ${
                                                    isBlocked
                                                        ? 'bg-gradient-to-br from-slate-700 to-slate-900 border border-slate-600/50'
                                                        : isClosed
                                                        ? 'bg-gradient-to-br from-red-950 to-slate-950 border border-red-900/30'
                                                        : card.type === 'virtual'
                                                        ? 'bg-gradient-to-tr from-violet-600 via-indigo-600 to-indigo-800 border border-violet-500/30'
                                                        : 'bg-gradient-to-tr from-cyan-600 via-blue-600 to-indigo-800 border border-cyan-500/30'
                                                }`}
                                            >
                                                {/* Card Background Overlay for premium feel */}
                                                <div className="absolute inset-0 bg-radial-gradient from-white/10 to-transparent pointer-events-none" />

                                                {/* Top Row: Type and Chip */}
                                                <div className="flex justify-between items-start z-10">
                                                    <div>
                                                        <Badge className="bg-white/20 text-white border-none font-semibold backdrop-blur-md">
                                                            {card.type.toUpperCase()}
                                                        </Badge>
                                                        <span className="ml-2 text-xs opacity-75 font-semibold">
                                                            {isBlocked ? 'BLOCKED' : isClosed ? 'CLOSED' : 'ACTIVE'}
                                                        </span>
                                                    </div>
                                                    {/* Chip Graphic */}
                                                    <div className="w-10 h-8 bg-yellow-400/90 rounded-md border border-yellow-300/40 relative overflow-hidden shadow-inner">
                                                        <div className="absolute top-1/2 left-0 right-0 h-[1px] bg-yellow-600/60" />
                                                        <div className="absolute top-0 bottom-0 left-1/2 w-[1px] bg-yellow-600/60" />
                                                    </div>
                                                </div>

                                                {/* Middle Row: PAN */}
                                                <div className="my-2 z-10 flex items-center gap-3">
                                                    <span className="font-mono text-lg tracking-widest text-shadow-md">
                                                        {showPanId === card.id
                                                            ? card.masked_pan.replace(/\*/g, '4') // Simulate PAN view
                                                            : card.masked_pan}
                                                    </span>
                                                    {!isClosed && (
                                                        <button
                                                            onClick={() => setShowPanId(showPanId === card.id ? null : card.id)}
                                                            className="text-white/70 hover:text-white hover:scale-110 transition-all"
                                                        >
                                                            {showPanId === card.id ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                                        </button>
                                                    )}
                                                </div>

                                                {/* Bottom Row: Holder Name, Expiry, Logo */}
                                                <div className="flex justify-between items-end z-10">
                                                    <div>
                                                        <p className="text-[10px] uppercase tracking-wider opacity-60">Cardholder</p>
                                                        <p className="font-semibold text-sm tracking-wide">{card.cardholder_name}</p>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-[10px] uppercase tracking-wider opacity-60">Expires</p>
                                                        <p className="font-semibold text-sm">{card.expiration_date}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Link Account Info */}
                                            {account && (
                                                <div className="text-xs text-muted-foreground flex justify-between px-1">
                                                    <span>Linked Account: <strong>{account.label}</strong></span>
                                                    <span>IBAN: ...{account.account_number.slice(-4)}</span>
                                                </div>
                                            )}

                                            {/* Control Actions Row */}
                                            {!isClosed && (
                                                <div className="grid grid-cols-2 gap-2 mt-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            handleToggleStatus(card, isBlocked ? 'active' : 'blocked')
                                                        }
                                                        className="flex items-center gap-1 font-semibold border-border/80 hover:bg-accent/40"
                                                    >
                                                        {isBlocked ? (
                                                            <>
                                                                <Shield className="h-3.5 w-3.5 text-emerald-500" />
                                                                Activate
                                                            </>
                                                        ) : (
                                                            <>
                                                                <Ban className="h-3.5 w-3.5 text-amber-500" />
                                                                Block Card
                                                            </>
                                                        )}
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setSelectedCardForPin(card)}
                                                        className="flex items-center gap-1 font-semibold border-border/80 hover:bg-accent/40"
                                                    >
                                                        <Key className="h-3.5 w-3.5 text-indigo-500" />
                                                        Set Card PIN
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>

                {/* Set PIN Overlay Modal */}
                {selectedCardForPin && (
                    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                        <CardContainer className="w-full max-w-sm border-border/50 shadow-2xl bg-card">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Key className="h-5 w-5 text-indigo-500" />
                                    Configure PIN
                                </CardTitle>
                                <CardDescription>
                                    Assign a secure 4-digit PIN for card: <strong>{selectedCardForPin.masked_pan}</strong>.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSetPin} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="pin">Enter New PIN *</Label>
                                        <Input
                                            id="pin"
                                            type="password"
                                            maxLength={4}
                                            required
                                            value={pinForm.data.pin}
                                            onChange={(e) => pinForm.setData('pin', e.target.value)}
                                            placeholder="4 digits (e.g. 1234)"
                                            className="text-center text-lg tracking-widest font-bold"
                                        />
                                        <InputError message={pinForm.errors.pin} />
                                    </div>

                                    <div className="flex gap-2 justify-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => {
                                                setSelectedCardForPin(null);
                                                pinForm.reset();
                                            }}
                                            className="font-semibold"
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={pinForm.processing}
                                            className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm"
                                        >
                                            Save PIN
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </CardContainer>
                    </div>
                )}
            </div>
        </>
    );
}
