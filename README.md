● Update(resources\views\pages\admin\sales\payments\index.blade.php)
⎿ Added 12 lines, removed 23 lines
186 </div>  
 187  
 188 {{-- Stats cards --}}
189 - <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">  
 189 + <div wire:key="payments-stats-{{ $this->dateFrom }}-{{ $this->dateTo }}" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">  
 190  
 191 <flux:card class="p-4 border-l-4 border-l-emerald-500 dark:border-l-emerald-500 rounded-l-none!">  
 192 <div class="flex items-center justify-between">
193 <div>
194 - <flux:subheading class="text-xs! uppercase tracking-wide mb-1">  
 195 - Total Revenue  
 196 - </flux:subheading>  
 194 + <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Revenue</flux:subheading>  
 195 <flux:heading size="xl" class="text-2xl! font-bold! text-emerald-600"
196 x-data="countUp({ to: {{ $this->stats['revenue'] }}, decimals: 2, prefix: 'KES ' })" x-text="display">
197 </flux:heading>
200 - <flux:subheading class="text-xs! mt-1">Paid transactions</flux:subheading>  
 198 + <flux:subheading class="text-xs! mt-1">{{ $this->periodLabel }} · paid</flux:subheading>  
 199 </div>
202 - <div  
 203 - class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900 flex items-center justify-center shrink-0">  
 200 + <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900 flex items-center justify-center shrink-0">  
 201 <flux:icon.banknotes class="size-5 text-emerald-500" />
202 </div>
203 </div>
...
206 <flux:card class="p-4 border-l-4 border-l-amber-500 dark:border-l-amber-500 rounded-l-none!">
207 <div class="flex items-center justify-between">
208 <div>
212 - <flux:subheading class="text-xs! uppercase tracking-wide mb-1">  
 213 - Pending Value  
 214 - </flux:subheading>  
 209 + <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Pending Value</flux:subheading>  
 210 <flux:heading size="xl" class="text-2xl! font-bold! text-amber-600"
211 x-data="countUp({ to: {{ $this->stats['pending'] }}, decimals: 2, prefix: 'KES ' })" x-text="display">
212 </flux:heading>
218 - <flux:subheading class="text-xs! mt-1">Pending / Processing</flux:subheading>  
 213 + <flux:subheading class="text-xs! mt-1">Current outstanding</flux:subheading>  
 214 </div>
220 - <div  
 221 - class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900 flex items-center justify-center shrink-0">  
 215 + <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900 flex items-center justify-center shrink-0">  
 216 <flux:icon.clock class="size-5 text-amber-500" />
217 </div>
218 </div>
...
221 <flux:card class="p-4 border-l-4 border-l-red-500 dark:border-l-red-500 rounded-l-none!">
222 <div class="flex items-center justify-between">
223 <div>
230 - <flux:subheading class="text-xs! uppercase tracking-wide mb-1">  
 231 - Failed  
 232 - </flux:subheading>  
 224 + <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Failed</flux:subheading>  
 225 <flux:heading size="xl" class="text-2xl! font-bold! text-red-600"
226 x-data="countUp({ to: {{ $this->stats['failed'] }} })" x-text="display">
227 </flux:heading>
236 - <flux:subheading class="text-xs! mt-1">Failed transactions</flux:subheading>  
 228 + <flux:subheading class="text-xs! mt-1">{{ $this->periodLabel }}</flux:subheading>  
 229 </div>
230 <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-900 flex items-center justify-center shrink-0">
231 <flux:icon.exclamation-triangle class="size-5 text-red-500" />
...
236 <flux:card class="p-4 border-l-4 border-l-blue-500 dark:border-l-blue-500 rounded-l-none!">
237 <div class="flex items-center justify-between">
238 <div>
247 - <flux:subheading class="text-xs! uppercase tracking-wide mb-1">  
 248 - Total Transactions  
 249 - </flux:subheading>  
 239 + <flux:subheading class="text-xs! uppercase tracking-wide mb-1">Transactions</flux:subheading>  
 240 <flux:heading size="xl" class="text-2xl! font-bold!"
241 x-data="countUp({ to: {{ $this->stats['total'] }} })" x-text="display">
242 </flux:heading>
253 - <flux:subheading class="text-xs! mt-1">All time</flux:subheading>  
 243 + <flux:subheading class="text-xs! mt-1">{{ $this->periodLabel }}</flux:subheading>  
 244 </div>
255 - <div  
 256 - class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900 flex items-center justify-center shrink-0">  
 245 + <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900 flex items-center justify-center shrink-0">  
 246 <flux:icon.credit-card class="size-5 text-blue-500" />
247 </div>
248 </div>
